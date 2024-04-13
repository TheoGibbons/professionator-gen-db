<?php

namespace Professionator;

use \Professionator\Commands\BuildDatabase;

require_once('../app.php');

(new BuildDatabase)->index();