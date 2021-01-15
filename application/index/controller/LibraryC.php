<?php
    
    
    namespace app\index\controller;
    
    
    use app\common\Result;
    use app\index\model\LibraryBook;
    use app\index\model\LibraryBorrow;
    use app\index\model\LibraryClassification;
    use think\Controller;
    use think\Db;
    use think\Error;
    use think\Exception;
    use think\Session;
    
    class LibraryC
    {
        public function getAllBooks( $data = "" )
        {
            
            $allBook = Db ::table( "oa_library_book" )
                -> alias( "book" );
            if ( $data != "" ) {
                $librarySelect = $data[ 'librarySelect' ];
                $stage_select  = $data[ 'stage_select' ];
                $keyword       = $data[ 'keyword' ];
                if ( $librarySelect != "" and $librarySelect != 0 ) {
                    $allBook = $allBook -> where( "classification_id" , $librarySelect );
                }
                if ( $stage_select != "" ) {
                    $allBook = $allBook -> where( "borrow_status" , $stage_select );
                }
                if ( $keyword != "" ) {
                    $allBook = $allBook -> where( "name" , "like" , "%$keyword%" );
                }
                
            }
            $allBook = $allBook -> join( 'library_classification alc' , 'book.classification_id = alc.id' )
                -> field( "book_id,name,publisher,borrow_status,alc.classification" )
                -> select();
            $count   = count( $allBook );
            for ( $i = 0; $i < $count; $i++ ) {
                switch ( $allBook[ $i ][ 'borrow_status' ] ) {
                    case 0:
                        $allBook[ $i ][ 'borrow_status' ] = "可以借阅";
                        break;
                    case 1:
                        $allBook[ $i ][ 'borrow_status' ] = "已经借出";
                        break;
                    case 2:
                        $allBook[ $i ][ 'borrow_status' ] = "已下架";
                        break;
                    case 3:
                        $allBook[ $i ][ 'borrow_status' ] = "采购中";
                        break;
                    default:
                        $allBook[ $i ][ 'borrow_status' ] = "无";
                }
            }
            return Result ::returnResult( Result::SUCCESS , $allBook , $count );
        }
        
        
        public function getClassification()
        {
            $libraryClassifications = LibraryClassification ::all();
            $count                  = count( $libraryClassifications );
            return Result ::returnResult( Result::SUCCESS , $libraryClassifications , $count );
        }
        
        public function getMyBorrow()
        {
            $session = Session ::get( "info" );
            //$session = 1110030;
            $userBorrowBook = Db ::table( "oa_library_borrow" )
                -> alias( "borrow" )
                -> join( "library_book" , "borrow.book_id=library_book.book_id" )
                -> where( "borrower_id" , "=" , $session[ 'user_id' ] )
                -> field( "borrow.*,library_book.name" )
                -> select();
            for ($i=0;$i<count($userBorrowBook);$i++){
                if ($userBorrowBook[$i]['end_time'] == null){
                    $userBorrowBook[$i]['end_time'] = "尚未归还";
                }
            }
            $count          = count( $userBorrowBook );
            return Result ::returnResult( Result::SUCCESS , $userBorrowBook , $count );
        }
        
        public function lendBook( $data = "" )
        {
            $startTime = $this->getTime();
            $bookId = $data[ 'book_id' ];
            if ( $bookId != "" ) {
                $userInfo = Session ::get( 'info' );
                $lendBook = [ 'borrower_id' => $userInfo[ 'user_id' ] , 'book_id' => $bookId ,'start_time' => $startTime];
                $borrowBookStatus = Db::table("oa_library_book")->where("book_id",$bookId)->select();
                if ($borrowBookStatus[0]['borrow_status'] != 0){
                    $allBooks = $this -> getAllBooks();
                    return Result::returnResult(Result::LIBRARY_BORROW_FAIL,$allBooks['data'],$allBooks['count']);
                }
                Db ::startTrans();
                try {
                    Db ::table( "oa_library_borrow" )
                        -> insert( $lendBook );
                    Db ::table( "oa_library_book" ) -> where( "book_id" , $bookId )
                        -> update( [ 'borrow_status' => '1' ] );
                    Db ::commit();
                } catch ( Exception $exception ) {
                    Db ::rollback();
                    $allBooks = $this -> getAllBooks();
                    return Result ::returnResult( Result::ERROR , $allBooks[ 'data' ] , $allBooks[ 'count' ] );
                }
            }
            $allBooks = $this -> getAllBooks();
            return Result ::returnResult( Result::SUCCESS , $allBooks[ 'data' ] , $allBooks[ 'count' ] );
        }
        
        public function sendBook($data = ""){
            $bookId = $data['book_id'];
            $userInfo = Session::get("info");
            $userId = $userInfo['user_id'];
            $sendTime = $this->getTime();
            Db::startTrans();
            try {
                Db::table("oa_library_borrow")
                    ->where("book_id",$bookId)
                    ->where("borrower_id",$userId)
                    ->update(["end_time"=>$sendTime]);
                Db::table("oa_library_book")
                    ->where("book_id",$bookId)
                    ->update(["borrow_status" => "0"]);
                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                $myBorrow = $this -> getMyBorrow();
                return Result::returnResult(Result::LIBRARY_SENDBACK_FAIL,$myBorrow["data"],$myBorrow["count"]);
            }
            $myBorrow = $this -> getMyBorrow();
            return Result::returnResult(Result::SUCCESS,$myBorrow["data"],$myBorrow["count"]);
            
        }
        
        public function getTime(){
            date_default_timezone_set('PRC');
            return date("Y-m-d H:i:s");
        }
        
        public function addClassification($value = "" ){
            $data = null;
            $count = 0;
            Db::startTrans();
            try {
                Db::table("oa_library_classification")
                    ->insert(['classification' => $value]);
                Db::commit();
            }catch (Exception $exception){
                Db::rollback();
                return Result::returnResult(Result::ERROR,$data,$count);
            }
            return Result::returnResult(Result::SUCCESS,$data,$count);
            
        }
        
        public function addBook($data = ""){
            $returnData = null;
            $count = 0;
            $name = $data['book'];
            $publisher = $data['publisher'];
            $classification = $data['select'];
            $borrow_status = $data['status'];
            $introduce = $data['desc'];
            $insertData = ['name' => $name,'publisher' => $publisher,'classification_id' => $classification,'borrow_status' => $borrow_status,'introduce' => $introduce];
            Db::startTrans();
            try {
                $check = Db::table("oa_library_book")
                         ->insert($insertData);
                Db::commit();
            }catch (Exception $exception){
                Db::rollback();
                return Result::returnResult(Result::ERROR,$returnData,$count);
            }
            return Result::returnResult(Result::SUCCESS,$returnData,$count);
        }
        
        public function getAllUserBorrow(){
            $allUserBorrow =Db::table("oa_library_borrow")->alias("borrow");
            $allUserBorrow = $allUserBorrow ->join("oa_user user","borrow.borrower_id = user.user_id")
                                ->join("oa_library_book book","borrow.book_id = book.book_id");
            $allUserBorrow = $allUserBorrow ->field("borrow.*,book.name,user.user_name")
                                -> select();
            
            $count = count($allUserBorrow);
            
            foreach ($allUserBorrow as &$userBorrow){
                if ($userBorrow['end_time'] == null){
                    $userBorrow['end_time'] = "尚未归还";
                }
            }
            /*for ($i=0;$i<$count;$i++){
                if ($allUserBorrow[$i]['end_time'] == null ){
                    $allUserBorrow[$i]['end_time'] = "尚未归还";
                }
            }*/
            return Result::returnResult(Result::SUCCESS,$allUserBorrow,$count);
            
        }
        
        
    }