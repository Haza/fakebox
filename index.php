<?php

// The only setting.
$dir    = '/inbox';

include_once('functions.php');

// The "bootstrap" stuff.
fakebox_init($dir);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Fakebox</title>

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap.css" rel="stylesheet">

    <!-- Add custom CSS here -->
    <link href="css/simple-sidebar.css" rel="stylesheet">
  </head>

  <body>
    <div id="wrapper">
      <!-- Sidebar -->
      <div id="sidebar-wrapper">
        <ul class="sidebar-nav">
          <img src="images/fakebox.png" />
          <?php print generate_file_list($dir); ?>
        </ul>
      </div>

      <!-- Page content -->
      <div id="page-content-wrapper">
        <!-- Keep all page content within the page-content inset div! -->
        <div class="page-content inset">
          <div class="row">
            <div class="col-md-12">
            <?php if (isset($_GET['mail'])) { ?>
              <button class="header">Header</button>
              <a href="/?delete=<?php echo $_GET['mail'] ?>"><button class="delete">Delete</button></a>
            <?php } ?>
             <br />
              <?php
                print display_email_content($dir);
                print generate_delete_action();
              ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- JavaScript -->
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/custom.js"></script>

  </body>
</html>
