<?php 

namespace App\AdminFacades;


trait HasObjectConverter 
{
    public  function ToObject($object) {
         
        return (object)$object;
    }

    public  function ToArray($object) {
        return (array)$object;
    }

    public static function BuildArray() {

    }


    public static function BuildObject() {

    }
}