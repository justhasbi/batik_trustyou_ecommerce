<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'price'       => (float) $this->price,
            'motif'       => $this->motif,
            'fabric_type' => $this->fabric_type,
            'category'    => $this->whenLoaded('category', fn () => [
                'id'   => $this->category?->id,
                'name' => $this->category?->name,
            ]),
            'primary_image' => $this->whenLoaded('primaryImage', fn () =>
                $this->primaryImage?->path),
            'images' => $this->whenLoaded('images', fn () =>
                $this->images->map(fn ($img) => [
                    'id'         => $img->id,
                    'path'       => $img->path,
                    'is_primary' => $img->is_primary,
                ])),
            'sizes' => $this->whenLoaded('sizes', fn () =>
                $this->sizes->map(fn ($s) => [
                    'id'    => $s->id,
                    'size'  => $s->size,
                    'stock' => $s->stock,
                ])),
        ];
    }
}