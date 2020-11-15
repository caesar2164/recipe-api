<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getFileJSON($file) {
    if (file_exists($file)) {
        $handle = fopen($file, "r") or die("Unable to open file!");
        $raw = fread($handle, filesize($file));
        fclose($handle);
        $json = json_decode($raw, true);
        return $json;
    } else {
        return null;
    }
}
function printJSON($json) {
    header("Access-Control-Allow-Origin: *");
    $output = json_encode($json, JSON_PRETTY_PRINT);
    if(isset($_GET['pretty'])) {
        echo '<pre>'; print_r($output); echo '</pre>';
    } else {
        echo $output."\n\r";
    }
}

$SETTINGS = getFileJSON('settings.json');
if (!$SETTINGS) {
  $SETTINGS = [
      "API_KEY" => "DEFAULT_KEY"
  ];
}

if(isset($_GET['key']) && $_GET['key'] == $SETTINGS["API_KEY"]) {
    function getRecipePhoto($recipe_name) {
        $photo_path = 'recipes/'.$recipe_name.'/photo.jpg';
        if (file_exists($photo_path)) {
            return $_SERVER['HTTP_HOST'].'/'.$photo_path;
        } else {
            return $_SERVER['HTTP_HOST'].'/recipes/food.svg';
        }
    }

    function displayDocs($title, $message) {
        $doc_json = getFileJSON('documentation.json');
        $doc_json['title'] = $title;
        $doc_json['message'] = $message;
        printJSON($doc_json);
    }

    if(isset($_GET['request'])) {
        switch ($_GET['request']) {
            case 'help':
                displayDocs('Recipe API', 'Version 0.1.0');
                break;
            case 'get_one': # Get all of one recipe.
                $recipe_set = (isset($_GET['recipe']) ? true : false);
                $preparation_set = (isset($_GET['preparation']) ? true : false);
                if ($recipe_set || $preparation_set) {
                    if ($recipe_set) {
                        $output_name = $_GET['recipe'];
                        $output_file = 'recipes/'.$output_name.'/recipe.json';
                    } else {
                        $output_name = $_GET['preparation'];
                        $output_file = 'preparations/'.$output_name.'/recipe.json';
                    }
                    if (file_exists($output_file)) {
                        $json = getFileJSON($output_file);
                        if (!is_null($json)) {
                            $photo_url = getRecipePhoto($output_name);
                            $json['photo'] = $photo_url;
                            printJSON($json);
                        } else {
                            displayDocs('Parse Error.', 'There was a problem getting the recipe from the data directory.');
                        }
                    } else {
                        displayDocs('Not Found.', "The recipe you requested doesn't exist.");
                    }
                } else {
                    displayDocs('Missing Query String', 'The recipe query string is required for this request type');
                }
                break;
            case 'get_multiple': # Get multiple recipes.
                $output_json = [];
                $files = glob('recipes/*/recipe.json');
                shuffle($files);
                foreach($files as $index=>$file) {
                    $recipe_name = explode('/', $file)[1];
                    $json = getFileJSON($file);
                    if (!is_null($json)) {
                        $recipe_json = [];
                        $recipe_json['recipe_id'] = $recipe_name;
                        $recipe_json['recipe_name'] = $json['recipe_name'];
                        $recipe_json['description'] = $json['description'];
                        $display_tags = array_merge(
                            $json['tags'],
                            $json['key_ingredients']
                        );
                        $recipe_json['tags'] = $display_tags;
                        $recipe_json['times'] = $json['times'];
                        $recipe_json['yields'] = $json['yields'];
                        $recipe_json['photo'] = getRecipePhoto($recipe_name);
                        array_push($output_json, $recipe_json);
                    }
                }
                printJSON($output_json);
                break;
            case 'get_all_filters': # Get all unique tags from all recipes.

            case 'get_all_tags': # Get all unique tags from all recipes.
                $files = glob('recipes/*/recipe.json');
                $output_tags = [];
                foreach($files as $file) {
                    $recipe_name = explode('/', $file)[1];
                    $json = getFileJSON($file);
                    if (!is_null($json)) {
                        foreach ($json['tags'] as $tag) {
                            if (!in_array($tag, $output_tags)) {
                                array_push($output_tags, $tag);
                            }
                        }
                    }
                }
                sort($output_tags);
                printJSON($output_tags);
                break;
            case 'get_all_key_ingredients': # Get all unique tags from all recipes.
                $files = glob('recipes/*/recipe.json');
                $output_key_ingredients = [];
                foreach($files as $file) {
                    $recipe_name = explode('/', $file)[1];
                    $json = getFileJSON($file);
                    if (!is_null($json)) {
                        foreach ($json['key_ingredients'] as $tag) {
                            if (!in_array($tag, $output_key_ingredients)) {
                                array_push($output_key_ingredients, $tag);
                            }
                        }
                    }
                }
                sort($output_key_ingredients);
                printJSON($output_key_ingredients);
                break;
            case 'get_all_dish_types': # Get all unique tags from all recipes.
                $files = glob('recipes/*/recipe.json');
                $output_dish_types = [];
                foreach($files as $file) {
                    $recipe_name = explode('/', $file)[1];
                    $json = getFileJSON($file);
                    if (!is_null($json)) {
                        foreach ($json['dish_types'] as $tag) {
                            if (!in_array($tag, $output_dish_types)) {
                                array_push($output_dish_types, $tag);
                            }
                        }
                    }
                }
                sort($output_dish_types);
                printJSON($output_dish_types);
                break;
            case 'get_all_meal_types': # Get all unique tags from all recipes.
                $files = glob('recipes/*/recipe.json');
                $output_meal_types = [];
                foreach($files as $file) {
                    $recipe_name = explode('/', $file)[1];
                    $json = getFileJSON($file);
                    if (!is_null($json)) {
                        foreach ($json['meal'] as $tag) {
                            if (!in_array($tag, $output_meal_types)) {
                                array_push($output_meal_types, $tag);
                            }
                        }
                    }
                }
                sort($output_meal_types);
                printJSON($output_meal_types);
                break;
            default:
                displayDocs('Malformed Request', 'You must pass a recognized request type');
                break;
        }
    } else { displayDocs('Not Found.', 'There is something wrong with your request.'); }
} else {
  print "Invalid API Key. You must provide a valid API key.";
}
?>
