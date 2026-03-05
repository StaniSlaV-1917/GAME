<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $table = 'games';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'genre',
        'platform',
        'price',
        'rating',
        'description',
        'image',
        'stopgame_url_code',
        'is_featured',
        'is_new',
        'old_price',
        'discount_percent',
        'release_year',
    ];

    protected $appends = [
        'average_review_rating',
        'reviews_count',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function images()
    {
        return $this->hasMany(GameImage::class)->orderBy('sort_order');
    }

    public function getAverageReviewRatingAttribute(): ?float
    {
        $avg = $this->reviews()->avg('rating');
        return $avg ? round((float)$avg, 1) : null;
    }

    public function getReviewsCountAttribute(): int
    {
        return (int)$this->reviews()->count();
    }
}
