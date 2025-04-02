<?php
require_once('../config.php');
require_once('../includes/WfoImageCache.php');
require_once('header.php');



if(@$_POST){
    if(isset($_POST['singleImageUri'])){
        process_single_image();
    }else{
        process_csv_file();
    }
}else{
    render_forms();
}

require_once('footer.php');

function process_single_image(){

    $cache = new WfoImageCache();
    $uri = $_POST['singleImageUri'];
    if(!$uri){
        echo "You didn't set a uri!";
        return;
    }

    $cache->addImage($uri);


    echo "doing single image";
}

function process_csv_file(){

    echo "doing CSV file";


}

function render_forms(){

    ?>

    <hr/>

<!-- single or csv mode -->
<h2>Add a single image</h2>
<form method="POST" action="index.php">
    <div class="mb-3">
    <label for="singleImageUri" class="form-label">Single image URL to be added. Good for testing.</label>
            <input type="url" class="form-control" id="singleImageUri" name="singleImageUri" placeholder="https://example.com/123" >
    </div>
    <div class="mb-3">
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>
</form>

<hr/>

<h2>Ingest a CSV of images</h2>
<form method="POST" action="index.php">
<div class="mb-3">
  <label for="formFile" class="form-label">Upload CSV for processing</label>
    <input class="form-control" type="file" id="csvUpload" name="csvUpload" >
</div>


<div class="mb-3">
  <label for="exampleFormControlTextarea1" class="form-label">Action to perform</label>
  <select class="form-select" aria-label="Default select example">
  <option selected value="add">Add image(s) to the cache</option>
  <option value="check">Check image(s) are in the cache</option>
  <option value="remove">Remove image(s) from the cache</option>
</select>
</div>

<div class="mb-3">
    <button type="submit" class="btn btn-primary">Submit</button>
</div>
</form>
<hr/>


<p>Use this to register images with the image server. Upload a CSV file where the second column contains the URI of images to be cached in the server.</p>

<?php

} // render_forms


