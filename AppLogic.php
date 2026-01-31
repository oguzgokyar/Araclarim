<?php

class AppLogic {
    private $toolsFile;
    private $settingsFile;

    public function __construct() {
        $this->toolsFile = __DIR__ . '/data/tools.json';
        $this->settingsFile = __DIR__ . '/data/settings.json';
    }

    private function loadData($file) {
        if (!file_exists($file)) {
            return [];
        }
        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }

    private function saveData($file, $data) {
        if (!is_writable(dirname($file))) {
            throw new Exception("Data directory is not writable.");
        }
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }





    public function deleteTool($id) {
        $tools = $this->loadData($this->toolsFile);
        $tools = array_filter($tools, function($t) use ($id) {
            return $t['id'] !== $id;
        });
        $this->saveData($this->toolsFile, array_values($tools));
        return true;
    }

    public function incrementClick($id) {
        $tools = $this->loadData($this->toolsFile);
        foreach ($tools as &$tool) {
            if ($tool['id'] === $id) {
                $tool['clicks'] = ($tool['clicks'] ?? 0) + 1;
                break;
            }
        }
        $this->saveData($this->toolsFile, $tools);
        return true;
    }

    public function getSettings() {
        return $this->loadData($this->settingsFile);
    }

    public function updateSettings($data) {
        $currentSettings = $this->getSettings();
        $newSettings = array_merge($currentSettings, $data);
        // Ensure critical fields are present
        if (!isset($newSettings['openai_api_key'])) $newSettings['openai_api_key'] = '';
        
        $this->saveData($this->settingsFile, $newSettings);
        return ['status' => 'success'];
    }

    public function analyzeWithAI($query) {
        $settings = $this->getSettings();
        $apiKey = $settings['openai_api_key'] ?? ''; // We will keep the key name in JSON compatible, but in UI we call it Gemini Key

        if (empty($apiKey)) {
            throw new Exception("API Key is missing in settings.");
        }

        // Gemini API Endpoint
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

        // Prompt optimization
        $prompt = "Sen bir teknoloji küratörüsün. Verilen şu aracı analiz et: '$query'. 
        Eğer kullanıcı sadece isim verdiyse (örneğin 'Vite') bunun resmi web sitesini bul.
        
        Bana şu JSON formatında yanıt ver:
        {
            \"title\": \"Uygulama Adı (Orjinal Yazılışı)\",
            \"description\": \"Ne işe yaradığını NET anlatan açıklama (Süslü kelimeler YOK, Max 90 karakter)\",
            \"url\": \"Resmi Web Sitesi URL'i (Örn: https://vitejs.dev)\",
            \"icon\": \"Logo URL'i (Tercihen şeffaf PNG veya SVG, yoksa favicon URL'i)\",
            \"category\": \"En uygun kategori (TÜRKÇE: Geliştirme, Tasarım, Üretkenlik, Öğrenme, Yapay Zeka vb.)\",
            \"suggested_tags\": [\"Etiket1\", \"Etiket2\"], 
            \"rating_prediction\": 4.8
        }
        
        KURALLAR:
        1. 'suggested_tags' dizisi EN FAZLA 2 eleman içermeli. Hepsi TÜRKÇE olmalı (Örn: 'Arayüz', 'Derleme Aracı').
        2. 'category' alanı kesinlikle TÜRKÇE olmalı.
        3. 'url' alanı kesinlikle dolu olmalı.
        4. 'icon' alanı için yüksek kaliteli bir logo bulmaya çalış, bulamazsan boş bırak (ben hallederim).
        5. Sadece SAF JSON döndür. Markdown bloğu kullanma.";

        $data = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
             throw new Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);

        $result = json_decode($response, true);
        
        if (isset($result['error'])) {
            throw new Exception("Gemini API Error: " . ($result['error']['message'] ?? 'Unknown Error'));
        }

        // Extract Text from Gemini Response
        $content = $result['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        
        // Clean up markdown if Gemini adds it despite prompt
        $content = str_replace(['```json', '```'], '', $content);
        $content = trim($content);
        
        $json = json_decode($content, true);
        
        if (!$json) {
             // Fallback if JSON decode fails
             throw new Exception("Failed to parse AI response. Raw: " . substr($content, 0, 100) . "...");
        }
        
        // Auto-generate icon logic if AI didn't provide good one or URL provided directly
        if (empty($json['url']) && filter_var($query, FILTER_VALIDATE_URL)) {
            $json['url'] = $query;
        }

        // Fix Icon Logic
        $targetUrl = $json['url'] ?? $query;
        if (!preg_match("~^https?://~i", $targetUrl)) {
            $targetUrl = "https://" . $targetUrl;
        }

        // 1. Try to get metadata from the page directly (Best quality)
        $metaIcon = $this->fetchPageIcon($targetUrl);

        // 2. Decide Icon
        // Priority: AI Icon (if looks valid url) > Metadata Icon > Google Favicon > Default
        $aiIcon = $json['icon'] ?? '';
        
        // Simple check if AI icon is valid URL
        $aiIconValid = !empty($aiIcon) && filter_var($aiIcon, FILTER_VALIDATE_URL);

        if ($aiIconValid) {
            // Use AI icon
        } elseif ($metaIcon) {
            $json['icon'] = $metaIcon;
        } else {
             // Fallback to Google Favicon
             $domain = parse_url($targetUrl, PHP_URL_HOST);
             if ($domain) {
                  $json['icon'] = "https://www.google.com/s2/favicons?domain={$domain}&sz=64";
             } else {
                  $json['icon'] = "https://img.icons8.com/dusk/64/000000/console.png"; 
             }
        }
        
        // Ensure the data has the full URL
        if(empty($json['url'])) {
            $json['url'] = $targetUrl;
        }
        
        // Final safety check for Tags
        if (isset($json['suggested_tags']) && is_array($json['suggested_tags'])) {
             $json['suggested_tags'] = array_slice($json['suggested_tags'], 0, 2);
        }

        return $json;
    }

    private function fetchPageIcon($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html) return null;

        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);

        // 1. Check og:image
        $ogImage = $xpath->query('//meta[@property="og:image"]/@content');
        if ($ogImage->length > 0) {
            return $this->resolveUrl($url, $ogImage->item(0)->nodeValue);
        }

        // 2. Check twitter:image
        $twitterImage = $xpath->query('//meta[@name="twitter:image"]/@content');
        if ($twitterImage->length > 0) {
            return $this->resolveUrl($url, $twitterImage->item(0)->nodeValue);
        }

        // 3. Check link rel icon
        $icons = $xpath->query('//link[@rel="icon" or @rel="shortcut icon" or @rel="apple-touch-icon"]/@href');
        if ($icons->length > 0) {
            return $this->resolveUrl($url, $icons->item(0)->nodeValue);
        }

        return null;
    }

    private function resolveUrl($baseUrl, $relativeUrl) {
        if (filter_var($relativeUrl, FILTER_VALIDATE_URL)) {
            return $relativeUrl;
        }
        
        $parts = parse_url($baseUrl);
        $baseRoot = $parts['scheme'] . '://' . $parts['host'];
        
        if (strpos($relativeUrl, '/') === 0) {
            return $baseRoot . $relativeUrl;
        }
        
        return $baseRoot . '/' . $relativeUrl;
    }

    // --- Category Management ---

    public function getCategories() {
        $file = __DIR__ . '/data/categories.json';
        return $this->loadData($file);
    }

    public function saveCategories($data) {
        $file = __DIR__ . '/data/categories.json';
        $this->saveData($file, $data);
    }

    public function addCategory($name) {
        $categories = $this->getCategories();
        $newCategory = [
            'id' => uniqid('cat_'),
            'name' => $name,
            'subcategories' => []
        ];
        $categories[] = $newCategory;
        $this->saveCategories($categories);
        return $newCategory;
    }

    public function addSubCategory($parentId, $name) {
        $categories = $this->getCategories();
        foreach ($categories as &$cat) {
            if ($cat['id'] === $parentId) {
                $newSub = [
                    'id' => uniqid('sub_'),
                    'name' => $name
                ];
                $cat['subcategories'][] = $newSub;
                $this->saveCategories($categories);
                return $newSub;
            }
        }
        return false;
    }

    // --- Updated Tool Logic ---

    public function getTools($category = null, $subcategory = null, $search = null) {
        $tools = $this->loadData($this->toolsFile);
        
        if ($category) {
            $tools = array_filter($tools, function($tool) use ($category) {
                // If category matches Main Category Name (old logic compatibility) or ID?
                // Visual design uses names usually. Let's stick to checking names or IDs.
                // For simplicity, we compare names as stored in the tool.
                return ($tool['category'] ?? '') === $category;
            });
        }
        
        if ($subcategory) {
            $tools = array_filter($tools, function($tool) use ($subcategory) {
                return ($tool['subcategory'] ?? '') === $subcategory;
            });
        }

        if ($search) {
            $tools = array_filter($tools, function($tool) use ($search) {
                return stripos($tool['title'], $search) !== false || 
                       stripos($tool['description'] ?? '', $search) !== false ||
                       stripos($tool['category'] ?? '', $search) !== false;
            });
        }

        return array_values($tools);
    }

    public function addTool($data) {
        $tools = $this->loadData($this->toolsFile);
        
        $newTool = [
            'id' => uniqid(),
            'title' => $data['title'] ?? 'Untitled',
            'description' => $data['description'] ?? '',
            'url' => $data['url'] ?? '#',
            'icon' => $data['icon'] ?? '',
            'category' => $data['category'] ?? 'Uncategorized',     // Main Category Name
            'subcategory' => $data['subcategory'] ?? '',            // Sub Category Name
            'tags' => $data['tags'] ?? [],
            'clicks' => 0,
            'rating' => isset($data['rating']) ? (float)$data['rating'] : 0
        ];

        array_unshift($tools, $newTool);
        $this->saveData($this->toolsFile, $tools);
        return $newTool;
    }

    public function updateTool($id, $data) {
        $tools = $this->loadData($this->toolsFile);
        $updated = false;
        foreach ($tools as &$tool) {
            if ($tool['id'] === $id) {
                $tool['title'] = $data['title'] ?? $tool['title'];
                $tool['description'] = $data['description'] ?? $tool['description'];
                $tool['url'] = $data['url'] ?? $tool['url'];
                $tool['icon'] = $data['icon'] ?? $tool['icon'];
                // Only update category fields if provided
                if(isset($data['category'])) $tool['category'] = $data['category'];
                if(isset($data['subcategory'])) $tool['subcategory'] = $data['subcategory'];
                $tool['tags'] = $data['tags'] ?? $tool['tags'];
                $tool['rating'] = isset($data['rating']) ? (float)$data['rating'] : $tool['rating'];
                
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            $this->saveData($this->toolsFile, $tools);
            return true;
        }
        return false;
    }
    
    // --- Advanced Category Management ---
    
    public function updateCategory($id, $newName) {
        $categories = $this->getCategories();
        foreach ($categories as &$cat) {
            if ($cat['id'] === $id) {
                $cat['name'] = $newName;
                $this->saveCategories($categories);
                return true;
            }
        }
        return false;
    }

    public function deleteCategory($id) {
        $categories = $this->getCategories();
        $categories = array_filter($categories, function($cat) use ($id) {
            return $cat['id'] !== $id;
        });
        $this->saveCategories(array_values($categories));
        return true;
    }
    
    // Handle full structure update (for reordering and deep editing)
    public function updateAllCategories($newStructure) {
        $this->saveCategories($newStructure);
        return true;
    }

    // --- System & Update Logic ---

    public function getSystemInfo() {
        $settings = $this->getSettings();
        $versionFile = __DIR__ . '/version.txt';
        $currentVersion = file_exists($versionFile) ? trim(file_get_contents($versionFile)) : 'v1.0.0';
        
        return [
            'version' => $currentVersion,
            'github_repo' => $settings['github_repo_url'] ?? '',
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ];
    }
    
    public function checkUpdate() {
        $settings = $this->getSettings();
        $repoUrl = $settings['github_repo_url'] ?? '';
        
        if (empty($repoUrl)) {
            return ['error' => 'GitHub Repo URL ayarlanmamış.'];
        }

        // Parse owner and repo from URL
        // Expected format: https://github.com/owner/repo
        $path = parse_url($repoUrl, PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        if (count($pathParts) < 2) {
             return ['error' => 'Geçersiz Repo URL formatı.'];
        }
        
        $owner = $pathParts[0];
        $repo = $pathParts[1];
        
        // Fetch latest commit from main/master
        $apiUrl = "https://api.github.com/repos/$owner/$repo/commits/main";
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Antigravity-App');
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (!isset($data['sha'])) {
            // Try 'master' if 'main' fails
             $apiUrl = "https://api.github.com/repos/$owner/$repo/commits/master";
             $ch = curl_init($apiUrl);
             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
             curl_setopt($ch, CURLOPT_USERAGENT, 'Antigravity-App');
             $response = curl_exec($ch);
             curl_close($ch);
             $data = json_decode($response, true);
        }

        if (!isset($data['sha'])) {
             return ['error' => 'Güncelleme bilgisi alınamadı. Repo gizli veya hatalı olabilir.'];
        }
        
        $remoteHash = substr($data['sha'], 0, 7);
        
        $versionFile = __DIR__ . '/version.txt';
        $localHash = file_exists($versionFile) ? trim(file_get_contents($versionFile)) : '';

        return [
            'has_update' => ($localHash !== $remoteHash),
            'local_hash' => $localHash ?: 'Bilinmiyor',
            'remote_hash' => $remoteHash,
            'download_url' => "https://github.com/$owner/$repo/archive/" . ($data['sha']) . ".zip"
        ];
    }
    
    public function performUpdate() {
        $check = $this->checkUpdate();
        if (isset($check['error'])) return ['status' => 'error', 'message' => $check['error']];
        if (!$check['has_update'] && empty($check['download_url'])) return ['status' => 'error', 'message' => 'Güncelleme yok.'];

        $url = $check['download_url'];
        $zipFile = __DIR__ . '/update_temp.zip';
        
        // 1. Download Zip
        file_put_contents($zipFile, fopen($url, 'r'));
        
        // 2. Extract Zip
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            $extractPath = __DIR__ . '/update_temp_extract/';
            if (!is_dir($extractPath)) mkdir($extractPath, 0777, true);
            
            $zip->extractTo($extractPath);
            $zip->close();
            
            // 3. Move files (Preserving data/)
            // usage: GitHub zips usually have a root folder like 'Repo-main'
            $files = scandir($extractPath);
            $rootFolder = '';
            foreach($files as $f) {
                if($f !== '.' && $f !== '..' && is_dir($extractPath . $f)) {
                    $rootFolder = $f;
                    break;
                }
            }
            
            if ($rootFolder) {
                $sourceDir = $extractPath . $rootFolder . '/';
                $destDir = __DIR__ . '/';
                
                $this->recursiveCopy($sourceDir, $destDir);
            }
            
            // 4. Update local version file
            file_put_contents(__DIR__ . '/version.txt', $check['remote_hash']);
            
            // 5. Cleanup
            unlink($zipFile);
            $this->recursiveDelete($extractPath);
            
            return ['status' => 'success', 'message' => 'Güncelleme başarıyla tamamlandı.'];
        } else {
            return ['status' => 'error', 'message' => 'Zip dosyası açılamadı.'];
        }
    }
    
    private function recursiveCopy($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    // Skip data folder
                    if ($file === 'data') continue;
                    $this->recursiveCopy($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    // Skip sensitive files if necessary, but generally we want to update everything else
                    // config.php ?? If user has custom config, we might want to skip it.
                    // For now, assuming config is not used or is generic.
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
    
    private function recursiveDelete($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
          (is_dir("$dir/$file")) ? $this->recursiveDelete("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}
