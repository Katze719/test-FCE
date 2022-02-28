<?

session_start();

/**
 * gibt einen zufälligen 16 stelligen string zurück
 * @return string 16
 */
function random_string(): string
{
    if (function_exists('random_bytes')) {
        $bytes = random_bytes(16);
        $str = bin2hex($bytes);
    } else if (function_exists('openssl_random_pseudo_bytes')) {
        $bytes = openssl_random_pseudo_bytes(16);
        $str = bin2hex($bytes);
    } else {
        $str = md5(uniqid('htwgfresaj8rea8734330g91gbrq841qgbq9', true));
    }
    return $str;
}

/**
 * löscht alle ordner und dateien zum user vom server
 * @param string $username
 * @return bool
 */
function file_delete_all_from_user($username): bool
{
    if (!$username) {
        return false;
    }
    $path = "/usr/www/users/firstcuz/test/includes/upload/files/{$username}";
    array_map('unlink', glob("$path/*.*"));
    rmdir($path);
    return true;
}

/**
 * löscht das bild
 * @param string $file_path
 * @return bool
 */
function file_delete($file_path): bool
{
    require("/usr/www/users/firstcuz/test/includes/db.php");
    unlink($file_path);
    $sql = 'DELETE FROM `base_db`.`uploads` WHERE location = "' . $file_path . '"';
    if ($conn->query($sql) === TRUE) {
        return true;
    }
    return false;
}

/**
 * return den path zum profil bild des angebenen users
 * @param string $username
 * @return string file path
 */
function file_get_profile_pic($username = NULL): string
{
    if ($username == NULL) {
        $username = $_SESSION['session_user']['username'];
    }
    $path = "/usr/www/users/firstcuz/test/includes/upload/files/{$username}/";
    $arr = scandir($path);
    foreach ($arr as $elem) {
        if (strpos($elem, '_profile.') !== false) {
            return str_replace("/usr/www/users/firstcuz/", '/', realpath("{$path}{$elem}"));
        }
    }
    return "No Profile Pic found!";
}

/**
 * gibt einen array mit file paths zurück zum bilder anzeigen auf der website
 * @param string $username
 * @return array string[]
 */
function file_get_array($username = NULL): array
{
    if ($username == NULL) {
        $username = $_SESSION['session_user']['username'];
    }

    $path = "/usr/www/users/firstcuz/test/includes/upload/files/{$username}/";
    $file = scandir($path);
    $arr = array();
    $i = 0;
    foreach ($file as $elem) {
        if (!str_contains($elem, "VID=") || !str_contains($elem, "_profile")) {
            $arr[$i] = str_replace("/usr/www/users/firstcuz/", '/', realpath("{$path}{$elem}"));
            $i++;
        }
    }
    return $arr;
}

/**
 * file_upload()
 * 
 * @param _FILES $file
 * @param string $username
 * @param bool $profile_pic
 * @param bool $vid
 * @return string
 * 
 * standart file upload $file MUSS! $_FILES[] sein 
 * $username bitte trotzdem angeben auch wen nicht immer notwendig
 * das dient zum debugging
 * 
 * $profile_pic bitte true setzten wen mit dem bild das hochgeladen wird das
 * profil bild gemeint ist, damit weis die funktion das es das alte ersetzen soll
 * und kein zweites erstellen soll.
 * 
 * $vid auf true setzten wen es sich um ein video handeln soll
 */
function file_upload($file, $username = NULL, $profile_pic = false, $vid = false): string
{
    if (!$file['name']) {
        return "No file found!";
    }
    require("/usr/www/users/firstcuz/test/includes/db.php");

    if ($username == NULL) {
        $username = $_SESSION['session_user']['username'];
    }

    $new_filename = random_string();

    $upload_folder = "/usr/www/users/firstcuz/test/includes/upload/files/{$username}/"; //Das Upload-Verzeichnis
    $filename = pathinfo($file['name'], PATHINFO_FILENAME);
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!is_dir($upload_folder)) {
        mkdir($upload_folder);
    }

    if (!$vid) {
        $allowed_extensions = array('png', 'jpg', 'jpeg');
        if (!in_array($extension, $allowed_extensions)) {
            return "filetype is not supported! must be png, jpg or jpeg";
        }
        $max_size = 10 * 1000 * 1024; //10 MB
        if ($file['size'] > $max_size) {
            return "file to big!";
        }
    } else {
        $allowed_extensions = array('mp4', 'mov', 'm4v');
        if (!in_array($extension, $allowed_extensions)) {
            return "filetype is not supported! must be mp4, mov or m4v";
        }
        $max_size = 100 * 1000 * 1024; //100 MB
        if ($file['size'] > $max_size) {
            return "file to big!";
        }
    }

    if (!$vid) {
        if (function_exists('exif_imagetype')) { //exif_imagetype erfordert die exif-Erweiterung
            $allowed_types = array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF);
            $detected_type = exif_imagetype($file['tmp_name']);
            if (!in_array($detected_type, $allowed_types)) {
                return "pic analyzer: could contain virus!";
            }
        }
    } else {
        $getID3 = new getID3;
        $ID3file = $getID3->analyze($file['tmp_name']);
        if ($ID3file['playtime_seconds'] > 10) {
            return "file is to long! 10sec MAX";
        }
        if ($ID3file['video']['resolution_x'] < 640 && $ID3file['video']['resolution_y'] < 480) {
            return "please check your video, this resolution is not supported!";
        }
    }

    if ($vid) {
        $new_filename = "VID=" . $new_filename;
    }

    $new_path = $upload_folder . $new_filename . '.' . $extension;

    if (file_exists($new_path)) {
        $id = 1;
        do {
            $new_path = $upload_folder . $new_filename . '_' . $id . '.' . $extension;
            $id++;
        } while (file_exists($new_path));
    }

    if ($profile_pic) {
        $new_path = $upload_folder . '_profile.' . $extension;
        $new_filename = '_profile';
        if (file_exists($new_path)) {
            file_delete($new_path);
        }
    }

    $up1 = mysqli_real_escape_string($conn, str_replace("/usr/www/users/firstcuz/", '/', $new_path));
    $up2 = mysqli_real_escape_string($conn, $username);
    $up3 = mysqli_real_escape_string($conn, $new_filename);
    $up3_1 = mysqli_real_escape_string($conn, $filename);
    $up4 = mysqli_real_escape_string($conn, $file['size']);
    $up5 = mysqli_real_escape_string($conn, $extension);


    $sql = 'INSERT INTO `base_db`.`uploads`(
        location,
        username,
        filename,
        origin_filename,
        filesize,
        filetype
        ) VALUES (
        "' . $up1 . '",
        "' . $up2 . '",
        "' . $up3 . '",
        "' . $up3_1 . '",
        "' . $up4 . '",
        "' . $up5 . '"
    );';
    if ($conn->query($sql) === TRUE) {
        move_uploaded_file($file['tmp_name'], $new_path);
        return "Uploaded!";
    } else {
        return "Error: " . $sql . "<br><b>" . $conn->error . "</b>";
    }
}

function file_upload_video($file, $username = NULL): string
{
    return file_upload($file, $username, false, true);
}
