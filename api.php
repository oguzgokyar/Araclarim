<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'AppLogic.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$app = new AppLogic();

try {
    $input = json_decode(file_get_contents("php://input"), true);

    switch ($action) {
        case 'get_tools':
            $category = $_GET['category'] ?? null;
            $subcategory = $_GET['subcategory'] ?? null;
            $search = $_GET['search'] ?? null;
            echo json_encode($app->getTools($category, $subcategory, $search));
            break;

        case 'get_categories':
            echo json_encode($app->getCategories());
            break;

        case 'add_category':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            if (!isset($input['name'])) throw new Exception("Name required");
            echo json_encode($app->addCategory($input['name']));
            break;

        case 'add_sub_category':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            if (!isset($input['parent_id']) || !isset($input['name'])) throw new Exception("Parent ID and Name required");
            echo json_encode($app->addSubCategory($input['parent_id'], $input['name']));
            break;

        case 'add_tool':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            echo json_encode($app->addTool($input));
            break;

        case 'delete_tool':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            if (!isset($input['id'])) throw new Exception("ID required");
            echo json_encode(['success' => $app->deleteTool($input['id'])]);
            break;
        
        case 'click_tool':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            if (!isset($input['id'])) throw new Exception("ID required");
            echo json_encode(['success' => $app->incrementClick($input['id'])]);
            break;

        case 'get_settings':
            $settings = $app->getSettings();
            // Security: Hide API Key in frontend response if needed, but for settings page we might need it mask?
            // Actually, usually we don't send the secret key back to UI unless necessary.
            // Let's allow it for now as per requirement (user edits it).
            echo json_encode($settings);
            break;

        case 'update_settings':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            echo json_encode($app->updateSettings($input));
            break;

        case 'analyze_url':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            if (!isset($input['query'])) throw new Exception("Query required");
            echo json_encode($app->analyzeWithAI($input['query']));
            break;

        case 'update_tool':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            if (!isset($input['id'])) throw new Exception("ID required");
            echo json_encode(['success' => $app->updateTool($input['id'], $input)]);
            break;

        case 'update_categories':
             // Full structure update for reordering/editing
            if ($method !== 'POST') throw new Exception("Method not allowed");
            echo json_encode(['success' => $app->updateAllCategories($input)]);
            break;
            
        case 'get_system_info':
            echo json_encode($app->getSystemInfo());
            break;
            
        case 'check_update':
            echo json_encode($app->checkUpdate());
            break;
            
        case 'perform_update':
            if ($method !== 'POST') throw new Exception("Method not allowed");
            echo json_encode($app->performUpdate());
            break;

        default:
            throw new Exception("Invalid action");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
