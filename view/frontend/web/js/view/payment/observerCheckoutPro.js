/**
 * Copyright © MercadoPago. All rights reserved.
 *
 * @author      Mercado Pago
 * @license     See LICENSE for license details.
 */
window.addEventListener('message', (event) => {
    'use strict';

    var dataExit;

    if (typeof event.data === 'string') {
        try {
            dataExit = JSON.parse(event.data);
            
            if (dataExit.action === 'finalize') {
                document.location.reload(true);
            }
        }
        catch (e) { }
    }
});
