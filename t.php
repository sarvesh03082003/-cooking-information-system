<?php
session_start();

// -----------------------------------------------------------------------------
// Database Connection
// -----------------------------------------------------------------------------
$conn = new mysqli("localhost", "root", "", "recipe_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// -----------------------------------------------------------------------------
// API Configuration & Request Count Tracking
// -----------------------------------------------------------------------------
$spoonacular_api_key = "08784d3586a24a9e9385ed702a35141"; // Your Spoonacular API key

// Track the number of requests to determine which API to use
$request_count = 0;
$request_file = "request_count.txt";

// Read the current request count from file, or initialize the file if not found
if (file_exists($request_file)) {
    $request_count = (int) file_get_contents($request_file);
} else {
    file_put_contents($request_file, $request_count);
}

// Default to using Spoonacular unless request count exceeds a threshold
$use_spoonacular = true;
$api_key = $spoonacular_api_key;
$api_url = '';

if ($request_count >= 150) {
    // Switch to TheMealDB if request count exceeds 150
    $use_spoonacular = false;
} 

// -----------------------------------------------------------------------------
// Handle API Search Request
// -----------------------------------------------------------------------------
$search_results = [];
$recipe_detail = null;
$error = '';

if (isset($_GET['search_food'])) {
    if (isset($_GET['query']) && !empty($_GET['query'])) {
        $query = urlencode($_GET['query']);

        // Choose the API URL based on which API to use
        if ($use_spoonacular) {
            $api_url = "https://api.spoonacular.com/recipes/complexSearch?query=$query&apiKey=$api_key&number=10";
        } else {
            // TheMealDB does not require an API key
            $api_url = "https://www.themealdb.com/api/json/v1/1/search.php?s=$query";
        }

        // Use cURL to send the request to the API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        // Decode the API response
        $data = json_decode($response, true);

        // Process the results based on the API used
        if ($use_spoonacular) {
            if (isset($data['results']) && !empty($data['results'])) {
                $search_results = $data['results'];
            } else {
                $error = "No results found for the given query.";
            }
        } else {
            if (isset($data['meals']) && !empty($data['meals'])) {
                $search_results = $data['meals'];
            } else {
                $error = "No results found for the given query.";
            }
        }
    } else {
        $error = "Please enter a Food Name to Search.";
    }
}

// -----------------------------------------------------------------------------
// Fetch Detailed Recipe Information if Requested
// -----------------------------------------------------------------------------
if (isset($_GET['recipe_id'])) {
    $recipe_id = intval($_GET['recipe_id']);
    if ($use_spoonacular) {
        $detail_url = "https://api.spoonacular.com/recipes/$recipe_id/information?apiKey=$api_key";
    } else {
        $detail_url = "https://www.themealdb.com/api/json/v1/1/lookup.php?i=$recipe_id";
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $detail_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $recipe_detail = json_decode($response, true);
}

// -----------------------------------------------------------------------------
// Update the Request Count
// -----------------------------------------------------------------------------
if ($request_count < 150) {
    $request_count++;
    file_put_contents($request_file, $request_count);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipe Management System</title>
    <style>
        /* Basic styling and responsive layout */
        body {
            font-family: 'Arial', sans-serif;
            background-color: rgb(248, 248, 248);
            color: #333;
            transition: background-color 0.3s ease;
        }
        header {
            background-color: #28a745;
            color: white;
            padding: 20px;
            text-align: center;
            transition: background-color 0.3s ease;
        }
        header h1 {
            margin: 0;
        }
        header p {
            font-size: 1.2rem;
            margin-top: 10px;
        }
        /* Navigation Menu */
        nav {
            background-color: #333;
            padding: 10px;
            text-align: center;
        }
        nav a {
            color: white;
            padding: 14px 20px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        nav a:hover {
            background-color: #575757;
        }
        /* Main Content Area */
        .content {
            padding: 20px;
        }
        .recipe-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            opacity: 0;
            animation: fadeIn 1s forwards;
        }
        .recipe {
            background-color: white;
            border-radius: 8px;
            width: 300px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .recipe:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }
        .recipe img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            max-height: 250px;
            object-fit: cover;
        }
        .recipe h2 {
            font-size: 22px;
        }
        .recipe a {
            color: #3498db;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .recipe a:hover {
            text-decoration: underline;
            color: #2980b9;
        }
        /* Fade-in Animation */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        /* Recipe Details Section */
        .recipe-detail {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 20px auto;
            opacity: 0;
            animation: fadeIn 1s forwards;
        }
        .recipe-detail img {
            width: 100%;
            max-height: 400px;
            border-radius: 8px;
            object-fit: cover;
        }
        .recipe-detail ul {
            list-style-type: none;
            padding: 0;
        }
        .recipe-detail ul li {
            background-color: #ecf0f1;
            margin: 5px 0;
            padding: 8px;
            border-radius: 4px;
        }
        .error {
            color: red;
            font-size: 16px;
            margin-top: 20px;
        }
        .instructions {
            margin-top: 20px;
            background-color: #ecf0f1;
            padding: 10px;
            border-radius: 4px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .instructions ul {
            list-style-type: none;
            padding-left: 20px;
        }
        .instructions ul li {
            margin-bottom: 10px;
            line-height: 1.6;
        }
        /* Search Bar */
        .search-bar {
            margin-top: 20px;
            background-color: #f8f8f8;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .search-bar input[type="text"] {
            width: 300px;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-right: 10px;
        }
        .search-bar button {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .search-bar button:hover {
            background-color: #218838;
        }
        /* Footer Styles */
        footer {
            background-color: #28a745;
            color: white;
            text-align: center;
            padding: 15px;
            position: fixed;
            width: 100%;
            bottom: 0;
            left: 0;
        }
        footer p {
            margin: 0;
        }
    </style>
</head>
<body>

<!-- Dynamic Header -->
<header>
    <h1>Art Of Cooking Information System</h1>
    <p>
        <?php
        // Display a personalized greeting if the user is logged in; otherwise, show a default message.
        if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
            echo "Welcome, " . htmlspecialchars($_SESSION['username']) . "!";
        } else {
            echo "Welcome, Guest!";
        }
        ?>
    </p>
</header>

<!-- Navigation Bar -->
<nav>
    <!-- "Home" link: directs to 'tyu.php' if a user is logged in, otherwise to 'lop.php' -->
    <a href="<?php echo isset($_SESSION['user_id']) ? 'tyu.php' : 'lop.php'; ?>">Home</a>
    <a href="#">Search Recipes</a>
    <a href="about.php">About</a>
    
    <!-- Display Login link only when user is not logged in -->
    <?php if (!isset($_SESSION['user_id'])): ?>
        <a href="lop.php">Login</a>
    <?php endif; ?>
</nav>

<div class="content">
    <!-- Search Bar Section -->
    <div class="search-bar">
        <form method="GET">
            <input type="text" name="query" placeholder="Search for recipes..." required>
            <button type="submit" name="search_food">Search</button>
        </form>
    </div>

    <!-- Display Search Results -->
    <?php if (!empty($search_results)): ?>
        <h3>Search Results:</h3>
        <div class="recipe-list">
            <?php foreach ($search_results as $recipe): ?>
                <div class="recipe">
                    <?php
                        // Determine image URL based on API source
                        $image_url = $use_spoonacular 
                            ? 'https://spoonacular.com/recipeImages/'.$recipe['id'].'-312x231.jpg' 
                            : $recipe['strMealThumb'];
                        // Fallback image if URL is invalid
                        if (empty($image_url) || !@getimagesize($image_url)) {
                            $image_url = 'path/to/placeholder-image.jpg';
                        }
                    ?>
                    <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($recipe['title'] ?? $recipe['strMeal']); ?>">
                    <h2><?php echo htmlspecialchars($recipe['title'] ?? $recipe['strMeal']); ?></h2>
                    <a href="?search_food=1&recipe_id=<?php echo $recipe['id'] ?? $recipe['idMeal']; ?>">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif (!empty($error)): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <!-- Display Recipe Details if Available -->
    <?php if (isset($recipe_detail)): ?>
        <div class="recipe-detail">
            <h2><?php echo htmlspecialchars($recipe_detail['title'] ?? $recipe_detail['meals'][0]['strMeal']); ?></h2>
            <img src="<?php echo $use_spoonacular ? $recipe_detail['image'] : $recipe_detail['meals'][0]['strMealThumb']; ?>" alt="Recipe Image">
            <p><strong>Summary:</strong> <?php echo $use_spoonacular ? strip_tags($recipe_detail['summary']) : $recipe_detail['meals'][0]['strInstructions']; ?></p>
            
            <!-- Ingredients List -->
            <h3>Ingredients:</h3>
            <ul>
                <?php
                if ($use_spoonacular) {
                    foreach ($recipe_detail['extendedIngredients'] as $ingredient): ?>
                        <li><?php echo htmlspecialchars($ingredient['original']); ?></li>
                    <?php endforeach;
                } else {
                    for ($i = 1; $i <= 20; $i++) {
                        $ingredient_key = "strIngredient$i";
                        if (!empty($recipe_detail['meals'][0][$ingredient_key])) {
                            echo "<li>" . htmlspecialchars($recipe_detail['meals'][0][$ingredient_key]) . "</li>";
                        }
                    }
                }
                ?>
            </ul>

            <!-- Instructions Section -->
            <h3>Instructions:</h3>
            <div class="instructions">
                <ul>
                    <?php
                    if ($use_spoonacular) {
                        if (isset($recipe_detail['analyzedInstructions'][0]['steps'])) {
                            foreach ($recipe_detail['analyzedInstructions'][0]['steps'] as $step): ?>
                                <li><?php echo htmlspecialchars($step['step']); ?></li>
                            <?php endforeach;
                        } else {
                            echo "<li>No detailed instructions available.</li>";
                        }
                    } else {
                        $instructions = explode("\n", $recipe_detail['meals'][0]['strInstructions']);
                        foreach ($instructions as $instruction):
                            if (!empty(trim($instruction))):
                    ?>
                                <li><?php echo htmlspecialchars($instruction); ?></li>
                    <?php
                            endif;
                        endforeach;
                    }
                    ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<footer>
    <p>&copy; 2024 Art of Cooking Information System. All rights reserved.</p>
</footer>

</body>
</html>







