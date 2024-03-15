<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class FileUploadController extends Controller
{

    public function index()
    {
        $files = File::files(public_path('files'));
        $urls=[];
        $fileName = [];
        if(!empty($files)){
            foreach($files as $file){
                $urls[] = asset('/files/'.$file->getFilename());
                $fileName[] = $file->getFilename();
            }
           
        }
        $jsonurl = json_encode($urls,JSON_UNESCAPED_SLASHES);
        // dd($urls);
        return view('index',compact('fileName','jsonurl'));
    }
    public function uploadFile(Request $request)
    {
        $targetDir = 'files';
        $fileBlob = 'fileBlob'; 
        if ($request->hasFile($fileBlob)) {
           

            $file = $request->file($fileBlob);
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $fileId = $request->input('fileId');
            $index = $request->input('chunkIndex');
            $totalChunks = $request->input('chunkCount');
            $targetPath = public_path($targetDir);
            $targetFile = $targetPath . DIRECTORY_SEPARATOR . $fileName;

            if ($totalChunks > 1) {
                $targetFile .= '_' . str_pad($index, 4, '0', STR_PAD_LEFT);
            }

            if ($file->move($targetDir, $fileName)) {
                $chunks = glob("{$targetPath}/{$fileName}_*");
                $allChunksUploaded = $totalChunks > 1 && count($chunks) == $totalChunks;

                if ($allChunksUploaded) {
                    $outFile = $targetPath . DIRECTORY_SEPARATOR . $fileName;
                    $this->combineChunks($chunks, $outFile);
                }

              
                $targetUrl = asset("{$targetDir}/{$fileName}");
                $zoomUrl = asset("{$targetDir}/{$fileName}");

                return response()->json([
                    'chunkIndex' => $index,
                    'initialPreview' => $targetUrl,
                    'initialPreviewConfig' => [
                        [
                            'type' => 'image',
                            'caption' => $fileName,
                            'key' => $fileId,
                            'fileId' => $fileId,
                            'size' => $fileSize,
                            'zoomData' => $zoomUrl,
                        ]
                    ],
                    'append' => true
                ]);
            } else {
                return response()->json(['error' => 'Error uploading chunk ' . $index]);
            }
        }

        return response()->json(['error' => 'No file found']);
    }

    private function combineChunks($chunks, $targetFile)
    {
        $handle = fopen($targetFile, 'a+');

        foreach ($chunks as $file) {
            fwrite($handle, file_get_contents($file));
        }

        foreach ($chunks as $file) {
            @unlink($file);
        }

        fclose($handle);
    }
    public function deleteFile(Request $request){
                 $fileToDelete = public_path('files/' . urldecode($request->file));
// dd($fileToDelete);
                if (File::exists($fileToDelete)) {
                    File::delete($fileToDelete);
                    return response()->json(['success' => 'File deleted successfully']);
                } else {
                    return response()->json(['error' => 'File not found']);
                }

    }

  

   
}
