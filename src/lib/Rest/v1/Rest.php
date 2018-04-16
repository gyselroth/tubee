<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest\v1;

use Micro\Http\Response;

class Rest
{
    /**
     * Entrypoint.
     *
     * @return Response
     */
    public function get(): Response
    {
        $data = [
            'name' => 'tubee',
            'version' => 1,
        ];

        return (new Response())->setCode(200)->setBody($data);
    }
}
