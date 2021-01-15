<?php
    
    
    namespace app\index\model;
    
    
    use think\Model;

    class LibraryBook extends Model
    {
        public function getBorrow_StatusAttr($value)
        {
            $status = [0=>'可以借用',1=>'已经借出',2=>'已下架',3=>'采购中'];
            return $status[$value];
        }
    }