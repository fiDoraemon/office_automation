<?php
    
    
    namespace app\index\controller;
    
    
    use app\common\Result;
    use app\index\model\LibraryBook;
    use app\index\model\LibraryClassification;
    use think\Controller;
    use think\Db;
    use think\Session;

    class LibraryC
    {
        public function getAllBooks($data = ""){
            
            $allBook = Db::table("oa_library_book")
                ->alias('book');
            if ($data != ""){
                $librarySelect = $data['librarySelect'];
                $stage_select = $data['stage_select'];
                $keyword = $data['keyword'];
                if ($librarySelect != "" and $librarySelect != 0){
                    $allBook = $allBook ->where("classification_id",$librarySelect);
                }
                if ($stage_select != ""){
                    $allBook = $allBook -> where("borrow_status",$stage_select);
                }
                if ($keyword != ""){
                    $allBook = $allBook -> where("name","like","%$keyword%");
                }
                
            }
            $allBook =$allBook ->join('library_classification alc','book.classification_id = alc.id')
                ->field("book.id,book_id,name,publisher,borrow_status,alc.classification")
                ->select();
            $count = count($allBook);
            for ($i=0;$i<$count;$i++){
                switch ($allBook[$i]['borrow_status']){
                    case 0:
                        $allBook[$i]['borrow_status'] = "可以借阅";
                        break;
                    case 1:
                        $allBook[$i]['borrow_status'] = "已经借出";
                        break;
                    case 2:
                        $allBook[$i]['borrow_status'] = "已下架";
                        break;
                    case 3:
                        $allBook[$i]['borrow_status'] = "采购中";
                        break;
                    default:
                        $allBook[$i]['borrow_status'] = "无";
                }
            }
            return Result::returnResult(Result::SUCCESS,$allBook,$count);
        }
        
        
        
        public function getClassification(){
            $libraryClassifications = LibraryClassification ::all();
            $count = count($libraryClassifications);
            return Result::returnResult(Result::SUCCESS,$libraryClassifications,$count);
        }
        
        public function getMyBorrow(){
            $session = Session ::get( "info" );
            //$session = 1110030;
            $userBorrowBook = Db::table("oa_library_borrow")
                ->alias("borrow")
                ->join("library_book","borrow.book_id=library_book.book_id")
                ->where("borrower_id","=",$session['user_id'])
                ->field("borrow.*,library_book.name")
                ->select();
            $count = count($userBorrowBook);
            return Result::returnResult(Result::SUCCESS,$userBorrowBook,$count);
        }
        
        
        public function lendBook($data = ""){
            $bookId = $data['book_id'];
            if ($bookId  != ""){
                $userInfo = Session ::get( 'info' );
                $lendBook = ['borrower_id'=>$userInfo['user_id'],'book_id'=>$bookId];
                $insertData = Db ::transaction( function () use ( $lendBook,$bookId ) {
                    Db ::table( "oa_library_borrow" )
                        -> insert( $lendBook );
                    Db::table("oa_library_book")->where("book_id",$bookId)
                        ->update(['borrow_status'=>'1']);
                });
            }
            $allBooks = $this -> getAllBooks();
            return Result::returnResult(GEARMAN_SUCCESS,$allBooks['data'],$allBooks['count']);
    
        }
        

    }