<?php
if ($_FILES['image']['error'] == UPLOAD_ERR_OK) {
    $dist = 'images/' . uniqid() . '.png';
    // сохранение фото
    move_uploaded_file($_FILES['image']['tmp_name'], $dist);

    $image = imagecreatefromstring(file_get_contents($dist));

    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocatealpha($image, 0, 0, 0, 100);
    imagefilledrectangle($image, 0, 0, 399, 29, $white);

    $font = 'Roboto.ttf';

    list($width, $height) = getimagesize($dist);

    $yStart = 50;
    $xDraw = 0;
    $yDraw = $yStart;

    // отрисовка текста
    for ($i = 0; $i < 30; $i++) {
        for ($j = 0; $j < 100; $j++) {
            imagettftext($image, 24, 45, $xDraw, $yDraw, $black, $font, 'Image Redactor');
            $xDraw += 200;
        }
        $xDraw = 0;
        $yDraw += 100;
    }


    imagepng($image, $dist);

    imagedestroy($image);

    echo json_encode(['url' => $dist]);
}
