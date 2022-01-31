<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteRequest;
use App\Http\Requests\GalleryRequest;
use App\Models\User;
use App\Repos\GalleryRepo;
use App\Services\GalleryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    protected $galleryRepo;
    protected $galleryService;

    public function __construct(GalleryRepo $galleryRepo, GalleryService $galleryService)
    {
        $this->galleryRepo = $galleryRepo;
        $this->galleryService = $galleryService;
    }

    public function getAllImages(User $user): array
    {
        $galleries = $this->galleryRepo->getAllOrderByCreatedAt($user, 'asc');
        $imagesCollection = $galleries->map->only(['filepath']);

        return $this->galleryService->getLinks($imagesCollection);
    }

    public function getRecentImages(User $user, int $amount): array
    {
        $galleries = $this->galleryRepo->getSomeOrderByCreatedAt($user, 'desc', $amount);
        $imagesCollection = $galleries->map->only(['filepath']);

        return $this->galleryService->getLinks($imagesCollection);
    }

    public function saveImages(User $user, GalleryRequest $request): void
    {
        $directory = implode('_', preg_split("/[@.]+/", $user->email));

        DB::transaction(function () use ($user, $directory, $request) {
            $this->galleryRepo->saveImages($user, $directory, $request->file('file'));
        });
    }

    public function deleteImage(User $user, DeleteRequest $request): void
    {
        $filepath = str_replace('/storage/', '', $request->input('path'));

        DB::transaction(function () use ($user, $filepath) {
            $this->galleryRepo->deleteImage($user, $filepath);
            Storage::disk('public')->delete($filepath);
        });
    }
}
