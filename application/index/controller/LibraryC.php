<?php
    
    
    namespace app\index\controller;
    
    
    use app\common\Result;
    use app\index\model\LibraryBook;
    use app\index\model\LibraryClassification;
    use think\Controller;
    use think\Db;

    class LibraryC
    {
        public function getBooks(){
            $allBook = LibraryBook::all();
            return Result::returnResult(Result::SUCCESS,$allBook);
        }
        public function getClassification(){
            $libraryClassifications = LibraryClassification ::all();
            return Result::returnResult(Result::SUCCESS,$libraryClassifications);
        }
    }