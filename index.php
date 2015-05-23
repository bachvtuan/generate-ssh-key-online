<?php 
    
  function getPost( $name ){
    return isset( $_POST[$name]) ? $_POST[$name]: "";
  }

  function tempDir() {
    $tempfile=tempnam(sys_get_temp_dir(),'');
    if (file_exists($tempfile)) { unlink($tempfile); }
    mkdir($tempfile);
    if (is_dir($tempfile)) { return $tempfile; }
  }

  function deleteDir($dirPath) {
    if (! is_dir($dirPath)) {
      throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
      $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
      if (is_dir($file)) {
          deleteDir($file);
      } else {
          unlink($file);
      }
    }
    rmdir($dirPath);
  }

  $error = '';

  if ($_POST['bitlength'] ){
    $bitlength = $_POST['bitlength'];
    $name = $_POST['name'];
    $passphrase = $_POST['passphrase'];

    $bitlength_arr = array("1024", "2048", "4096",);
    if ( !in_array($bitlength, $bitlength_arr) ){
      $error = "Invalid bitlength";
    }

    if ( $name !='' && !preg_match('/^[a-zA-Z0-9@_.]+$/', $name)){
      $error = "Invalid name";
    }

    if ( $passphrase != '' &&  !preg_match('/^[a-zA-Z0-9!@_\#\$\%\*\(\)\_\+\.]+$/', $passphrase)){
      $error = "Invalid passphrase";
    }

    if ( $error == '' ){
      $temp_dir = tempDir();
      $command = sprintf('ssh-keygen -b %d -t rsa -C "%s" -N "%s" -f %s/id_rsa', $bitlength, $name, $passphrase, $temp_dir );
      //die($command);
      exec( $command );

      $zipname = 'ssh_keys.zip';
      $zip_path =  $temp_dir .'/ssh_keys.zip';
      print("$zip_path");
      $zip = new ZipArchive;
      $zip->open($zip_path, ZipArchive::CREATE);

      $zip->addFile($temp_dir."/id_rsa", "id_rsa");
      $zip->addFile($temp_dir."/id_rsa.pub", "id_rsa.pub");
      $zip->close();

      ob_get_clean();
      header("Pragma: public");
      header("Expires: 0");
      header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
      header("Cache-Control: private", false);
      header("Content-Type: application/zip");
      header("Content-Disposition: attachment; filename=" . basename($zipname) . ";" );
      header("Content-Transfer-Encoding: binary");
      header("Content-Length: " . filesize($zip_path));
      readfile($zip_path);
      
      deleteDir( $temp_dir );
      exit();


    }
  
    
  }

?>
<!DOCTYPE html>
<html>
<head>
  <title>Generate SSH key online</title>
  <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
   <meta name="description" content="Generate ssh key online with custom name, bitlength and passpharase" />
  <style type="text/css">

  </style>
</head>
<body >
  <div class="container" >
    <div class="row">
      <div class="col-md-2">
      </div>
      <div class="col-md-8">
        <h1>Generate ssh key online tool</h1>
        <?php if ( $error != '' ) :?>
          <div class="alert alert-warning" role="alert">
            <?php echo $error; ?>
          </div>
        <?php endif; ?>
        <form role="form" method="POST">
          <div class="form-group">
            <label for="name">Your name(email)</label>
            <input type="text" class="form-control" name="name" placeholder="Enter your name or email"
            value="<?php echo getPost('name'); ?>">
            <small>Allow: a-z, A-Z, 0-9, @ _ . </small>
          </div>
          <div class="form-group">
            <label for="passphrase">Enter passpharse</label>
            <input type="password" class="form-control" name="passphrase" placeholder="Passphrase" value="<?php echo getPost('passphrase'); ?>">
            <small>Allow: a-z, A-Z, 0-9, ! @ _  #  $ % * ( ) _ + .</small>
          </div>

          <div class="form-group">
            <label for="name">Bit length</label>
            <select class="form-control" name="bitlength">
              <option value="1024">1024</option>
              <option selected value="2048">2048</option>
              <option value="4096" >4096</option>
          </select>
          </div>
          
          <button type="submit" class="btn btn-primary">Generate</button>
        </form>
      </div>
      <div class="col-md-2">
      </div>
    </div>

  </div>

</body>
</html>