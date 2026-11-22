<?php

namespace App\Traits;

use App\Models\Upload;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Image;

trait CommonHelperTrait
{
    // protected $path_prefix = 'public';

    public function UploadImageCreate($image, $path)
    {
        if ($image && is_file($image)) {

            $extension = $image->guessExtension();
            $filename  = time() . Str::random(10) . '.' . $extension;

            $image_record       = new Upload();
            if (setting('file_system') == 's3') {

                $filePath           = s3Upload($path, $image);
                $imagePostSuccess   = Storage::disk('s3')->exists($filePath);
                $image_record->path = $filePath;
            }else{
                if (!File::exists($path)) {
                    File::makeDirectory($path, 0755, true);
                }
                $image->move($path, $filename);
                $image_record->path = $path .'/'. $filename;
            }
            $image_record->save();

            return $image_record->id;

        }
        return null;
    }


    public function UploadImageUpdate($image, $path, $upload_id)
    {
        if ($image && is_file($image)) {

            if($upload_id){
                $image_record = Upload::find($upload_id);
                if (setting('file_system') == 's3') {
                    Storage::disk('s3')->delete($image_record->path);
                }else{
                    if (!File::exists($path)) {
                        File::makeDirectory($path, 0755, true);
                    }
                    $file_path    = public_path($image_record->path);
                    if(file_exists($file_path)){
                        File::delete($file_path);
                    }
                }
            }else{
                $image_record = new Upload();
            }

            $extension          = $image->guessExtension();
            $filename           = time() . Str::random(10) . '.' . $extension;
            if (setting('file_system') == 's3') {
                $filePath       = s3Upload($path, $image);
                $image_record->path = $filePath;
            }else{
                $image->move($path, $filename);
                $image_record->path = $path .'/'. $filename;
            }

            $image_record->save();
            return $image_record->id;
        }
        return $upload_id;
    }

    public function UploadImageDelete($upload_id)
    {
        if($upload_id){
            $image_record = Upload::find($upload_id);
            if($image_record){
                $file_path    = public_path($image_record->path);
                if(file_exists($file_path)){
                    File::delete($file_path);
                }
                $image_record->delete();
            }
        }
        return true;
    }

    public function setEnvironmentValue($envKey, $envValue)
    {
        // The old strpos()-based version silently mis-set the WRONG line
        // (or corrupted the start of the file) whenever $envKey didn't
        // already exist in .env - strpos() returning false there got
        // used as a numeric offset (0), not detected as "not found". A
        // missing key is common here since .env is hand-maintained on
        // the live server, never deployed from this repo. Anchor the
        // match to an actual line (^KEY=...$) and append a new line
        // when it isn't found, instead of guessing.
        $envFile  = app()->environmentFilePath();
        $str      = file_get_contents($envFile);
        $envValue = '"'.$envValue.'"';
        $pattern  = '/^'.preg_quote($envKey, '/').'=.*$/m';

        if (preg_match($pattern, $str)) {
            $str = preg_replace($pattern, "{$envKey}={$envValue}", $str, 1);
        } else {
            $str = rtrim($str, "\n")."\n{$envKey}={$envValue}\n";
        }

        $fp = fopen($envFile, 'w');
        fwrite($fp, $str);
        fclose($fp);
        return true;
    }


    public function uploadDocuments($request, $previous_documents = [])
    {

        $previous_keys = [];
        if ( $previous_documents && count($previous_documents)) {  // when update documents
            foreach($previous_documents as $key=>$document) {
                if ( !in_array($key, $request->document_rows ?? [])) {
                    $this->UploadImageDelete(data_get($previous_documents, "$key.file"));
                }

                $previous_keys[] = $key;
            }
        }

        $upload_documents = array();

        if(isset($request->document_rows)) { // if upload documents

            foreach($request->document_rows as $row) {

                if ( in_array($row, $previous_keys)) { // when update documents then check for duplicate

                    $upload_documents[$row]['title'] = $request->document_names[$row];
                    $upload_documents[$row]['file']  = $this->UploadImageUpdate($request->document_files[$row] ?? '', 'backend/uploads/uploadDocuments', data_get($previous_documents, "$row.file"));

                } else {

                    $upload_documents[$row]['title'] = $request->document_names[$row];
                    $upload_documents[$row]['file']  = $this->UploadImageCreate($request->document_files[$row] ?? '', 'backend/uploads/uploadDocuments');

                }

            }
        }

        return $upload_documents;

    }

    public function uploadFilePath($file, $uploadPath, $oldFilePath = null)
    {
        // Delete old file if it exists
        if ($oldFilePath) {
            if (setting('file_system') == 's3') {
                if (Storage::disk('s3')->exists($oldFilePath)) {
                    Storage::disk('s3')->delete($oldFilePath);
                }
            } else {
                if (File::exists($oldFilePath)) {
                    File::delete($oldFilePath);
                }
            }
        }

        if ($file && is_file($file)) {
            $extension = $file->guessExtension();
            $filename  = time() . Str::random(10) . '.' . $extension;

            if (setting('file_system') == 's3') {

                $filePath = s3Upload($uploadPath, $file);
                Storage::disk('s3')->exists($filePath);
            } else {
                if (!File::exists($uploadPath)) {
                    File::makeDirectory($uploadPath, 0755, true);
                }

                $file->move($uploadPath, $filename);
                $filePath = $uploadPath . '/' . $filename;
            }

            return $filePath;
        }

        return null;
    }

    public function deleteFilePath( $filePath )
    {
        if ($filePath) {
            if (setting('file_system') == 's3') {
                if (Storage::disk('s3')->exists($filePath)) {
                    Storage::disk('s3')->delete($filePath);
                }
            } else {
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }

            return true;
        }

        return null;
    }
}
