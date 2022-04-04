<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Article;
use Livewire\WithPagination;

class Articles extends Component
{

    use WithPagination;

    public $active;
    public $q;

    public $sortBy = 'id';
    public $sortAsc = true;

    public $article;

    public $confirmingArticleDeletion = false;
    public $confirmingArticleAdd = false;

    protected $queryString = [
        'active' => ['except' => false],
        'q' => ['except' => ''],
        'sortBy' => ['except' => 'id'],
        'sortAsc' => ['except' => true],
    ];

    protected $rules = [
            'article.name' => 'required|string|min:4',
            'article.price' => 'required|numeric|between:1,10000',
            'article.quantity' => 'required|numeric|between:1,10000',
            'article.status' => 'boolean'
        ];

    public function render()
    {

        $articles = Article::where('user_id', auth()->user()->id)
                    ->when( $this->q, function($query) {
                        return $query->where(function ($query) {
                            $query->where('name', 'like', '%'.$this->q . '%')
                                ->orWhere('price', 'like', '%' .$this->q . '%')
                                ->orWhere('quantity', 'like', '%' .$this->q . '%');
                        });
                    })

            ->when($this->active, function( $query) {
                return $query->active();
            })

            ->orderBy( $this->sortBy, $this->sortAsc ? 'ASC' : 'DESC');


            $articles = $articles->paginate(10);

        return view('livewire.articles', [
            'articles' => $articles,

        ]);
    }

    public function updatingActive()
    {
        $this->resetPage();
    }

    public function updatingQ()
    {
        $this->resetPage();
    }

    public function sortBy ($field)
    {
        if($field == $this->sortBy) {
            $this->sortAsc = !$this->sortAsc;
        }
        $this->sortBy = $field;
    }

    public function confirmArticleDeletion ( $id)
    {
        //$article->delete();
        $this->confirmingArticleDeletion = $id;
    }

    public function deleteArticle (Article $article)
    {
        $article->delete();
        $this->confirmingArticleDeletion = false;
        session()->flash('message', 'Artículo eliminado exitosamente');
    }

    public function confirmArticleAdd ()
    {
        $this->reset(['article']);
        $this->confirmingArticleAdd = true;
    }

    public function saveArticle()
    {
        $this->validate();

        if (isset ($this->article->id )) {
            $this->article->save();
            session()->flash('message', 'Artículo actualizado exitosamente');
        }else {
            auth()->user()->articles()->create([
                'name' => $this->article['name'],
                'price' => $this->article['price'],
                'quantity' => $this->article['quantity'],
                'status' => $this->article['status'] ?? 0
            ]);
            session()->flash('message', 'Artículo creado exitosamente');

        }
        $this->confirmingArticleAdd = false;
    }

    public function confirmArticleEdit (Article $article)
    {
        $this->article = $article;
        $this->confirmingArticleAdd = true;
    }

}
