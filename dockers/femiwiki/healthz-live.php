<?php
// Liveness only: proves php-fpm executes PHP. Deliberately touches no
// dependency - a shared database or cache being down affects every instance
// equally, so failing them all out of the pool helps nobody. Capacity, not
// health, is what a liveness check answers. See femiwiki/femiwiki#467.
http_response_code( 200 );
