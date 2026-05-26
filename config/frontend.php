<?php

return [

    'url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:3001'), '/'),

    /*
    | Đường dẫn trang xem phim trên FE (tương đối domain).
    | Placeholder: {movie_slug}, {episode_slug}, {movie_id}, {episode_id}
    */
    'watch_path' => env('FRONTEND_WATCH_PATH', '/watch/{movie_slug}/{episode_slug}'),

];
