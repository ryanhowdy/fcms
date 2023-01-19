<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;

class DocumentController extends Controller
{
    /**
     * Show the documents main page
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $mimeDataLkup = [
            // word
            'application/msword' => [
                'icon'   => 'file-earmark-word',
                'folder' => 'document',
            ],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => [
                'icon'   => 'file-earmark-word',
                'folder' => 'document',
            ],
            'application/vnd.oasis.opendocument.text' => [
                'icon'   => 'file-earmark-word',
                'folder' => 'document',
            ],
            'application/rtf' => [
                'icon'   => 'file-earmark-word',
                'folder' => 'document',
            ],
            'text/plain' => [
                'icon'   => 'file-earmark-word',
                'folder' => 'document',
            ],
            // excel
            'application/vnd.ms-excel' => [
                'icon'   => 'file-earmark-excel',
                'folder' => 'document',
            ],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => [
                'icon'   => 'file-earmark-excel',
                'folder' => 'document',
            ],
            'application/vnd.oasis.opendocument.spreadsheet' => [
                'icon'   => 'file-earmark-excel',
                'folder' => 'document',
            ],
            // power point
            'application/vnd.ms-powerpoint' => [
                'icon'   => 'file-earmark-ppt',
                'folder' => 'presentation',
            ],
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => [
                'icon'   => 'file-earmark-ppt',
                'folder' => 'presentation',
            ],
            'application/vnd.oasis.opendocument.presentation' => [
                'icon'   => 'file-earmark-ppt',
                'folder' => 'presentation',
            ],
            // image
            'image/jpeg' => [
                'icon'   => 'file-earmark-image',
                'folder' => 'multimedia',
            ],
            'image/bmp' => [
                'icon'   => 'file-earmark-image',
                'folder' => 'multimedia',
            ],
            'image/gif' => [
                'icon'   => 'file-earmark-image',
                'folder' => 'multimedia',
            ],
            'image/png' => [
                'icon'   => 'file-earmark-image',
                'folder' => 'multimedia',
            ],
            'image/svg+xml' => [
                'icon'   => 'file-earmark-image',
                'folder' => 'multimedia',
            ],
            'image/tiff' => [
                'icon'   => 'file-earmark-image',
                'folder' => 'multimedia',
            ],
            'image/webp' => [
                'icon'   => 'file-earmark-image',
                'folder' => 'multimedia',
            ],
            // video
            'video/x-msvideo' => [
                'icon'   => 'file-earmark-play',
                'folder' => 'multimedia',
            ],
            'video/mp4' => [
                'icon'   => 'file-earmark-play',
                'folder' => 'multimedia',
            ],
            'video/mpeg' => [
                'icon'   => 'file-earmark-play',
                'folder' => 'multimedia',
            ],
            'video/webm' => [
                'icon'   => 'file-earmark-play',
                'folder' => 'multimedia',
            ],
            // audio
            'audio/mpeg' => [
                'icon'   => 'file-earmark-music',
                'folder' => 'multimedia',
            ],
            'audio/wav' => [
                'icon'   => 'file-earmark-music',
                'folder' => 'multimedia',
            ],
            'audio/webm' => [
                'icon'   => 'file-earmark-music',
                'folder' => 'multimedia',
            ],
            // archive
            'application/x-bzip' => [
                'icon'   => 'file-earmark-zip',
                'folder' => 'archive',
            ],
            'application/x-bzip2' => [
                'icon'   => 'file-earmark-zip',
                'folder' => 'archive',
            ],
            'application/gzip' => [
                'icon'   => 'file-earmark-zip',
                'folder' => 'archive',
            ],
            'application/vnd.rar' => [
                'icon'   => 'file-earmark-zip',
                'folder' => 'archive',
            ],
            'application/x-tar' => [
                'icon'   => 'file-earmark-zip',
                'folder' => 'archive',
            ],
            'application/zip' => [
                'icon'   => 'file-earmark-zip',
                'folder' => 'archive',
            ],
            'application/x-7z-compressed' => [
                'icon'   => 'file-earmark-zip',
                'folder' => 'archive',
            ],
            // pdf
            'application/pdf' => [
                'icon'   => 'file-earmark-pdf',
                'folder' => 'other',
            ],
        ];

        $documents = Document::latest()
            ->simplePaginate(25);

        $counts = [
            'document'     => 0,
            'presentation' => 0,
            'archive'      => 0,
            'multimedia'   => 0,
            'other'        => 0,
        ];
        foreach ($documents as $d)
        {
            if (isset($mimeDataLkup[$d->mime]))
            {
                $counts[ $mimeDataLkup[$d->mime]['folder'] ]++;
            }
            else
            {
                $counts['other']++;
            }
        }

        return view('documents.index', [
            'documents'    => $documents,
            'mimeDataLkup' => $mimeDataLkup,
            'counts'       => $counts,
        ]);
    }

    /**
     * Show the document create
     *
     * @return Illuminate\View\View
     */
    public function create()
    {
        return view('documents.create');
    }

    /**
     * Store the new document in the db
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Support\Facades\View
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required'],
            'description' => ['nullable'],
            'document'    => ['required', 'mimes:bmp,docm,dotm,epub,gif,jpg,jpeg,mp4,m4a,mp4a,mpga,mp2,mp2a,mp3,m2a,m3a,odc,otc,odb,odft,odg,otg,odi,oti,odp,otp,ods,ots,odt,odm,ott,oth,pdf,png,ppt,pps,pot,psd,svg,tiff,tif,txt,text,wav,wbmp,webm,wps,wks,wcm,wdb,xls,xlm,xla,xlc,xlt,xlw,zip'],
        ]);

        // Get the right path (storage/app/documents/X) for documents and make sure it exists
        $path = '/documents/'.Auth()->user()->id;
        Storage::makeDirectory($path);

        $file = $request->file('document');

        // Add the document to the db
        $document = new Document;

        $document->filename        = 'error';
        $document->name            = $request->name;
        $document->description     = $request->description;
        $document->mime            = $file->getMimeType();
        $document->created_user_id = Auth()->user()->id;
        $document->updated_user_id = Auth()->user()->id;

        $document->save();

        // Update the video db record filename
        $filename  = $document->id.'.'.$file->extension();

        $document->filename = $filename;

        $document->save();

        // Store the video
        Storage::putFileAs($path, $file, $filename);

        return redirect()->route('documents');
    }

    /**
     * download 
     * 
     * @param string  $file 
     * @return Illuminate\Http\Request $request
     */
    public function download ($file)
    {
        $document = Document::where('filename', $file)
            ->first();

        $userId = $document->created_user_id;

        return response()->download(storage_path('app/documents').'/'.$userId.'/'.$file);
    }
}
