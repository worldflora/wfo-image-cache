<?php
    // firstly handle logout calls
    if(isset($_GET['logout']) && $_GET['logout'] == true){
        unset($_SESSION['image_cache_user']);
        header('Location: /');
    }
?>
<p>
To add images to the cache you must be logged at the <a href="<?php echo WFO_FACET_SERVER ?>" target="facet_server">WFO Facet Server</a> <strong>first</strong>.
Then <a href="<?php echo WFO_FACET_SERVER ?>image_cache_login.php">click here</a>.
<p>
<?php

    // is there a token set in the 
    if(isset($_POST['key'])){
        // we have been posted to - presumably from the facet server
        // so we call the facet server directly to check the key is valid
        // and less than 10 minutes old

        $key = $_POST['key'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, WFO_FACET_SERVER . "image_cache_login_check.php");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
            http_build_query(array('key' => $key)));

        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        echo $response;
        $response = json_decode($response);

        curl_close($ch);

        if($response->success){
            $_SESSION['image_cache_user'] = $response;
            header('Location: /');
            exit;
        }else{
            unset($_SESSION['image_cache_user']);
            echo "<p>{$response->message}</p>";
        }

    }

?>


