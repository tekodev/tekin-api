<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// LIBRARIES
use App\Libraries\Helper;

// MODELS
use App\Models\Product;
use App\Models\Article;
use App\Models\Topic;
use App\Models\Banner;
use App\Models\Category;
use App\Models\system\SysConfig;

class ApiController extends Controller
{
    public function index()
    {
        return '[API] Tekin';
    }

    public function get_banner()
    {
        $data = Banner::where('status', 1)->orderBy('ordinal')->get();

        return response()->json([
            'status' => 'true',
            'message' => 'Successfully get banner data',
            'data' => $data
        ]);
    }

    public function get_product(Request $request)
    {
        $data = Product::with('category')
            ->whereNull('replaced_at');

        if ($request->category_id) {
            $data->where('category_id', $request->category_id);
        }

        $data = $data
            ->orderBy('ordinal', 'asc')
            ->get();

        $data->map(function ($item) {
            if ($item->image) {
                $item->image = asset($item->image);
            }
            return $item;
        });

        return response()->json([
            'status' => 'true',
            'message' => 'Successfully get product data',
            'data' => $data
        ]);
    }

    public function get_product_detail($id)
    {
        $data = Product::with('category')
            ->whereNull('replaced_at')
            ->find($id);

        if ($data) {
            if ($data->image) {
                $data->image = asset($data->image);
            }
            $data->makeHidden(['category_id']);
            return response()->json([
                'status' => 'true',
                'message' => 'Successfully get product detail',
                'data' => $data
            ]);
        }

        return response()->json([
            'status' => 'false',
            'message' => 'Product not found',
            'data' => null
        ], 404);
    }

    public function get_categories()
    {
        $data = Category::where('status', 1)->orderBy('position')->get();

        $data->map(function ($item) {
            if ($item->image) {
                $item->image = asset('uploads/category/' . $item->image);
            }
            return $item;
        });

        return response()->json([
            'status' => 'true',
            'message' => 'Successfully get categories data',
            'data' => $data
        ]);
    }

    public function get_config()
    {
        $data = SysConfig::first();

        if ($data) {
            if ($data->app_favicon) {
                $data->app_favicon = asset($data->app_favicon);
            }
            if ($data->app_logo_image) {
                $data->app_logo_image = asset($data->app_logo_image);
            }
        }

        return response()->json([
            'status' => 'true',
            'message' => 'Successfully get config data',
            'data' => $data
        ]);
    }

    public function get_topic()
    {
        $data = Topic::where('status', 1)
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'true',
            'message' => 'Successfully get topic data',
            'data' => $data
        ]);
    }

    public function get_blog(Request $request)
    {
        // GET THE DATA
        $data = Article::select(
            'articles.slug',
            'articles.title',
            'articles.thumbnail',
            'articles.summary',
            'articles.posted_at',
            'articles.author'
        )
            ->leftJoin('article_topic', 'articles.id', '=', 'article_topic.article_id')
            ->leftJoin('topics', 'article_topic.topic_id', '=', 'topics.id')
            ->where('articles.status', 1)
            ->orderBy('articles.posted_at', 'desc')
            ->groupBy(
                'articles.slug',
                'articles.title',
                'articles.thumbnail',
                'articles.summary',
                'articles.posted_at',
                'articles.author'
            );

        // FILTER BY TOPIC
        if ($request->topic) {
            $topic = Helper::validate_input_text($request->topic);
            $data->where('topics.name', $topic);
        }

        // FILTER BY KEYWORD
        if ($request->keyword) {
            $keyword = Helper::validate_input_text($request->keyword);
            $data->where(function ($query_where) use ($keyword) {
                $query_where->where('articles.title', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('articles.keywords', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('articles.summary', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('articles.content', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('topics.name', 'LIKE', '%' . $keyword . '%');
            });
        }

        // FILTER BY AUTHOR
        if ($request->author) {
            $author = Helper::validate_input_text($request->author);
            $data->where('articles.author', $author);
        }

        // GET TOTAL DATA
        $query = $data;
        $total = $query->get()->count();

        // PAGINATION
        $limit = 3;
        $page = 1;
        if ((int) $request->page) {
            $page = (int) $request->page;
        }
        if ($page < 1) {
            $page = 1;
        }
        $skip = ($page - 1) * $limit;

        $data = $data
            ->take($limit)
            ->skip($skip)
            ->get();

        return response()->json([
            'status' => 'true',
            'message' => 'Successfully get blog data',
            'data' => $data,
            'total' => $total
        ]);
    }

    public function get_blog_details(Request $request)
    {
        // GET PARAMATERS DATA
        $slug = $request->slug;

        // GET THE DATA
        $data = Article::select(
            'articles.slug',
            'articles.title',
            'articles.thumbnail',
            'articles.posted_at',
            'articles.author',
            'articles.summary',
            'articles.content',
            DB::raw('GROUP_CONCAT(topics.name SEPARATOR " | ") AS topics')
        )
            ->leftJoin('article_topic', 'articles.id', '=', 'article_topic.article_id')
            ->leftJoin('topics', 'article_topic.topic_id', '=', 'topics.id')
            ->where('articles.status', 1)
            ->where('articles.slug', $slug)
            ->orderBy('articles.posted_at', 'desc')
            ->groupBy(
                'articles.slug',
                'articles.title',
                'articles.thumbnail',
                'articles.posted_at',
                'articles.author',
                'articles.summary',
                'articles.content'
            )
            ->first();

        return response()->json([
            'status' => 'true',
            'message' => 'Successfully get blog details data',
            'data' => $data
        ]);
    }
}
