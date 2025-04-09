<?php

namespace App\Core\DB;

use PeeQL\PeeQL as PeeQLPeeQL;

class PeeQL {
    private PeeQLPeeQL $peeql;

    public function __construct() {
        $peeql = new PeeQLPeeQL();
    }
}

?>