import { masterApro } from './credit_card';
import { guestUserMLB, guestUserMPE } from './buyer';
import * as yapeData from './yape';

export const mlb = {
    url: process.env.STORE_URL,
    cards: {
        masterApro
    },
    guestUserMLB,
}

export const mpe = {
    url: process.env.STORE_URL,
    yapeData,
    guestUserMPE,
}
