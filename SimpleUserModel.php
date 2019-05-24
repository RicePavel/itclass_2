<?php

class SimpleUserModel {
    
    public function valid($userName, $password) {
        return ($userName == 'admin' && $password == 'qwerty');
    }
    
}

