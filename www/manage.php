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

    $image_id = $cache->addImage($uri);
    if($image_id){
        render_image_summary($image_id);
    }else{
        echo "<p>Something went wrong!</p>";
    }


}

function process_csv_file(){

    $render_upload_progress = false;
    if($_POST && isset($_FILES["input_file"]) && $_FILES["input_file"]["type"] == 'text/csv'){

        // we save the file by the user id and source.
        $now = time();
        $input_file_path = "../data/session_data/". session_id() ."/";

        if(!file_exists($input_file_path)) mkdir($input_file_path, 0777,true);

        move_uploaded_file($_FILES["input_file"]["tmp_name"], $input_file_path . '/in.csv');

        // load it all in the session because we will run through 
        // these things in ajax calls.
        $importer = new WfoImageCache($input_file_path, $_POST['input_mode']);
        $_SESSION['importer'] = serialize($importer);
        $render_upload_progress = true;

    }else{
        unset($_SESSION['importer']);
        $importer = false;
    }

    if($render_upload_progress){
        ?>

        <div id="upload_progress_bar" class="alert alert-warning" role="alert">
            <div><strong>Uploading ... </strong></div>
            <img src="" />
        </div>
        <div>
            <a href="index.php">Cancel</a>
        </div>
        <script>
            // call the progress bar every second till it is complete
            const upload_div = document.getElementById('upload_progress_bar');
            callProgressBar(upload_div, 'upload');
        </script>
        
        <?php
    }else{
        render_forms();
    }

}

function render_image_summary($image_id){
?>
    <div>
        <h2><?php echo $image_id ?></h2>
        <img src="/server/wfo/<?php echo $image_id ?>/full/,150/0/default.jpg" />
        <br/>
        <?php foreach(WFO_IMAGE_HEIGHTS as $h){ ?> 
            <a href="/server/wfo/<?php echo $image_id ?>/full/,<?php echo $h ?>/0/default.jpg" target="other" ><?php echo $h ?></a>, 
        <?php } ?>
        <a href="/server/wfo/<?php echo $image_id ?>/full/max/0/default.jpg" target="other" >Original</a>
    </div>
<?php
}

function render_forms(){

    ?>

<p>
    The image cache keeps a copy of the taxon images that are used in the portal and acts as a IIIF server (Level0)
    to supply smaller versions of the images for display on the web. This increases robustness and performance.
    It does not handle metadata about the images. It only deals in JPEG files.
</p>
<p>
    To cache an image you supply the URL for the original of that image. The image cache downloads a copy and gives it an ID.
    The ID is the MD5 hashcode of the URL the image was downloaded from. You can access the image or any of its derivatives using the <a href="https://iiif.io/api/image/3.0/">IIIF Image API</a>
    at the endpoint '/server' and its ID. An image url will look something like this.
</p>
<code>
https://<?php echo $_SERVER['HTTP_HOST'] ?>/server/wfo/{MD5 hash of source url}/full/{size}/0/default.jpg
</code>
<hr/>
<p>
    <strong>Cache size: </strong>
<?php
    $cmd = "du -d 0 -h " . WFO_FILE_CACHE ;
    $response = shell_exec($cmd);
    $parts = explode("\t", $response);
    echo trim($parts[0]);
//    echo shell_exec("pwd");
?>
</p>

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
<form method="POST" action="index.php" enctype="multipart/form-data">
<div class="mb-3">
  <label for="formFile" class="form-label">Upload CSV for processing</label>
    <input class="form-control" type="file" id="csvUpload" name="input_file" >
</div>


<div class="mb-3">
  <label for="exampleFormControlTextarea1" class="form-label">Action to perform</label>
  <select class="form-select" aria-label="Default select example" name="input_mode">
  <option selected value="add">Add image(s) to the cache</option>
  <option value="check">Check image(s) are in the cache</option>
  <option value="remove">Remove image(s) from the cache</option>
</select>
</div>

<div class="mb-3">
    <button type="submit" class="btn btn-primary">Submit</button>
</div>
</form>

<?php

$out_file_path = "../data/session_data/". session_id() ."/out.csv";
if(file_exists($out_file_path)){
    echo "<hr/>";
    echo "<p><strong>Output: </strong><a href=\"download_results.php\">Download annotated version of last CSV file</a>.<p>";
}

?>



<hr/>


<p>
</p>

<?php

} // render_forms


