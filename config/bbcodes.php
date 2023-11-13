<?php

return [

    'insert' => [
        'pattern' => '/\[ins\](.*?)\[\/ins\]/is',
        'replace' => '<ins>$1</ins>', 
        'content' => '$1',
    ],
    'delete' => [
        'pattern' => '/\[del\](.*?)\[\/del\]/is',
        'replace' => '<del>$1</del>',
        'content' => '$1',
    ],
	'heading1' => [
        'pattern' => '/\[h1\](.*?)\[\/h1\]/is',
        'replace' => '<h1>$1</h1>',
        'content' => '$1',
    ],
	'heading2' => [
        'pattern' => '/\[h2\](.*?)\[\/h2\]/is',
        'replace' => '<h2>$1</h2>',
        'content' => '$1',
    ],
	'heading3' => [
        'pattern' => '/\[h3\](.*?)\[\/h3\]/is',
        'replace' => '<h3>$1</h3>',
        'content' => '$1',
    ],
	'heading4' => [
        'pattern' => '/\[h4\](.*?)\[\/h4\]/is',
        'replace' => '<h4>$1</h4>',
        'content' => '$1',
    ],
	'heading5' => [
        'pattern' => '/\[h5\](.*?)\[\/h5\]/is',
        'replace' => '<h5>$1</h5>',
        'content' => '$1',
    ],
	'heading6' => [
        'pattern' => '/\[h6\](.*?)\[\/h6\]/is',
        'replace' => '<h6>$1</h6>',
        'content' => '$1',
    ],
	'bold' => [
        'pattern' => '/\[b\](.*?)\[\/b\]/is',
        'replace' => '<b>$1</b>',
        'content' => '$1',
    ],
	'italic' => [
        'pattern' => '/\[i\](.*?)\[\/i\]/is',
        'replace' => '<i>$1</i>',
        'content' => '$1',
    ],
	'underline' => [
        'pattern' => '/\[u\](.*?)\[\/u\]/is',
        'replace' => '<u>$1</u>',
        'content' => '$1',
    ],
	'named-link' => [
        'pattern' => '/\[url\=(.*?)\](.*?)\[\/url\]/is',
        'replace' => '<a href="$1">$2</a>',
        'content' => '$2',
    ],
	'link' => [
        'pattern' => '/\[url\](.*?)\[\/url\]/is',
        'replace' => '<a href="$1">$1</a>',
        'content' => '$1',
    ],
	'align' => [
        'pattern' => '/\[align\=(left|center|right)\](.*?)\[\/align\]/is',
        'replace' => '<div style="text-align: $1;">$2</div>',
        'content' => '$1',
    ],
	'image2' => [
        'pattern' => '/\[img\=(.*?)\]/is',
        'replace' => '<img src="$1"/>',
        'content' => '',
    ],
	'image' => [
        'pattern' => '/\[img\](.*?)\[\/img\]/is',
        'replace' => '<img src="$1"/>',
        'content' => '',
    ],
	'named-mailto' => [
        'pattern' => '/\[mail\=(.*?)\](.*?)\[\/mail\]/is',
        'replace' => '<a href="mailto:$1">$2</a>',
        'content' => '$2',
    ],
	'mailto' => [
        'pattern' => '/\[mail\](.*?)\[\/mail\]/is',
        'replace' => '<a href="mailto:$1">$1</a>',
        'content' => '$1',
    ],
	'font-family' => [
        'pattern' => '/\[font\=(.*?)\](.*?)\[\/font\]/is',
        'replace' => '<span style="font-family: $1;">$2</span>',
        'content' => '$2',
    ],
	'font-size' => [
        'pattern' => '/\[size\=(.*?)\](.*?)\[\/size\]/is',
        'replace' => '<span style="font-size: $1;">$2</span>',
        'content' => '$2',
    ],
	'color' => [
        'pattern' => '/\[color\=(.*?)\](.*?)\[\/color\]/is',
        'replace' => '<span style="color: $1;">$2</span>',
        'content' => '$2',
    ],
	'span' => [
        'pattern' => '/\[span\](.*?)\[\/span\]/is',
        'replace' => '<span>$1</span>',
        'content' => '$1',
    ],
	'span-class' => [
        'pattern' => '/\[span\=(.*?)\](.*?)\[\/span\]/is',
        'replace' => '<span class="$1">$2</span>',
        'content' => '$2',
    ],
	'blockquote' => [
        'pattern' => '/\[quote\](.*?)\[\/quote\]/is',
        'replace' => '<blockquote>$1</blockquote>',
        'content' => '$1',
    ],
	'youtube' => [
        'pattern' => '/\[video\](?:http(?:s)?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"\'>]+)\[\/video\]/is',
        'replace' => '<iframe class="youtube-player" type="text/html" width="640" height="385" src="http://www.youtube.com/embed/$1" allowfullscreen frameborder="0"></iframe>',
        'content' => '$1',
    ],
    'youtube-simple' => [
        'pattern' => '/\[video\]([^\?&\"\'>]+)\[\/video\]/is',
        'replace' => '<iframe class="youtube-player" type="text/html" width="640" height="385" src="http://www.youtube.com/embed/$1" allowfullscreen frameborder="0"></iframe>',
        'content' => '$1',
    ],
    'linebreak' => [
        'pattern' => '/\r\n/is',
        'replace' => '<br>',
        'content' => '',
    ],
];
