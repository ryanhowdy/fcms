<?php

require_once INC.'image_class.php';

class ImageTest extends PHPUnit_Framework_TestCase
{
    public function testGetExtension ()
    {
        $img = new Image(1);

        $img->name = 'normal.gif';
        $img->getExtension();

        $this->assertEquals($img->extension, 'gif');


        $img->name = 'not.so.normal.jpeg';
        $img->getExtension();

        $this->assertEquals($img->extension, 'jpeg');


        $img->name = 'bad filename.jo';
        $img->getExtension();

        $this->assertEquals($img->extension, 'jo');


        $img->name = 'missing_extension.';
        $img->getExtension();

        $this->assertEquals($img->extension, '');


        $img->name = 'no_extension';
        $img->getExtension();

        $this->assertEquals($img->extension, '');
    }

    public function testGetResizeSize ()
    {
        $img = new Image(1);

        //                                           orig      max
        //                                          w    h    w    h
        $sizeSquareBothSmall = $img->getResizeSize(500, 500, 600, 600);
        $sizeSquareBothBig   = $img->getResizeSize(500, 500, 200, 200);
        $sizeSquareWidthBig  = $img->getResizeSize(500, 500, 200, 600);
        $sizeTallWidthBig    = $img->getResizeSize(200, 400, 100, 500);
        $sizeTallHeightBig   = $img->getResizeSize(200, 400, 200, 200);
        $sizeTallBothBig     = $img->getResizeSize(200, 400, 100, 100);
        $sizeWideWidthBig    = $img->getResizeSize(400, 200, 100, 500);
        $sizeWideHeightBig   = $img->getResizeSize(400, 200, 500, 100);
        $sizeWideBothBig     = $img->getResizeSize(400, 200, 100, 100);

        $this->assertEquals($sizeSquareBothSmall, array(500, 500));
        $this->assertEquals($sizeSquareBothBig,   array(200, 200));
        $this->assertEquals($sizeSquareWidthBig,  array(200, 200));
        $this->assertEquals($sizeTallWidthBig,    array(100, 200));
        $this->assertEquals($sizeTallHeightBig,   array(100, 200));
        $this->assertEquals($sizeTallBothBig,     array( 50, 100));
        $this->assertEquals($sizeWideWidthBig,    array(100,  50));
        $this->assertEquals($sizeWideHeightBig,   array(200, 100));
        $this->assertEquals($sizeWideBothBig,     array(100,  50));
    }

    public function testIsValid ()
    {
        $img = new Image(1);

        $img->name = 'normal.gif';
        $img->type = 'image/gif';
        $img->getExtension();

        $this->assertTrue($img->isValid());


        $img->name = 'not.so.normal.jpeg';
        $img->type = 'image/jpeg';
        $img->getExtension();

        $this->assertTrue($img->isValid());


        $img->name = 'bad filename.jo';
        $img->type = 'text/plain';
        $img->getExtension();

        $this->assertFalse($img->isValid());


        $img->name = 'no_extension.';
        $img->type = 'image/jpeg';
        $img->getExtension();

        $this->assertFalse($img->isValid());
    }
}
