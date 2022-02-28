# upload-inc.php

## documentation

### random_string()
Gibt einen random string zurück
```php
$string = random_string();
```

### file_delete_all_from_user()
Löscht alle dateien von einem user auf dem server
gibt dir true oder false zurück
```php
$bool = file_delete_all_from_user($username);
```

### file_delete()
zum löschen eines bildes
gibt dir true oder false zurück
```php
$bool = file_delete($file_path);
```

### file_get_profile_pic()
gibt dir den dateipfad zum bild anzeigen des profilbildes vom user
```php
$string = file_get_profile_pic($username);
```

### file_get_array()
gibt dir alle bilder eines users zurück mit aussnahme von videos und profilbild in einem array
```php
$array = file_get_array($username);
```

### file_upload()
file upload zu einem user
die funktion gibt immer eine msg zurück, ob alles funktioniert hat oder ob es fehler gab
```php
$string = file_upload($_FILES['myFile'], $username);
// im falle eines profilbildes
$string = file_upload($_FILES['myFile'], $username, true);
// im falle eines videos
$string = file_upload($_FILES['myFile'], $username, false, true);
// oder man verwendet
$string = file_upload_video($_FILES['myFile'], $username);
```
