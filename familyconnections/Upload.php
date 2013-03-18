<?php

interface Upload
{
    public function displayForm();
    public function upload($photo, $formData);
    public function getLastPhotoId();
}
