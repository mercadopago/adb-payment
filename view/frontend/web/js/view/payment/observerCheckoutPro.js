/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Bruno Elisei <brunoelisei@o2ti.com>
 * @license     See LICENSE for license details.
 */
window.addEventListener('message', (event) => {
    'use strict';

    var dataExit;

    if (typeof event.data === 'string') {
        dataExit = JSON.parse(event.data);

        if (dataExit.action === 'finalize') {
            document.location.reload(true);
        }
    }
});
