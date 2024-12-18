const PHONE_APRO = '111111111';
const PHONE_REJECTED_CALL_AUTHORIZATION = '111111112';
const PHONE_REJECTED_INSUFFICIENT_AMOUNT = '111111113';
const PHONE_REJECTED_OTHER_REASON = '111111114';
const PHONE_REJECTED_NOT_ALLOWED = '111111115';
const PHONE_REJECTED_MAX_ATTEMPTS = '111111116';
const PHONE_REJECTED_SECURITY_CODE = '111111117';
const PHONE_REJECTED_FORM_ERROR = '111111118';
const CODE = '123456';
const PHONE_EMPTY_FORM_ERROR = '';
const PHONE_INCOMPLETE_FORM_ERROR = '111';
const PHONE_INVALID_FORM_ERROR = 'abc';
const CODE_EMPTY_FORM_ERROR = '';
const CODE_INCOMPLETE_FORM_ERROR = '123';

export const yapeApproved = {
    phone: PHONE_APRO,
    code: CODE,
    messageSuccess: {
        PT:'Obrigado pela sua Compra!',
        ES:'Gracias por su compra!',
        EN:'Thank you for your purchase!',
    }
}

export const yapeRejectedCallForAuthorize = {
    ...yapeApproved,
    phone: PHONE_REJECTED_CALL_AUTHORIZATION,
    messageError: {
        PT:'Não foi possível processar seu pagamento. Você pode entrar em contato com Yape para saber os motivos ou tentar novamente com este ou outro meio de pagamento.',
        ES:'No se pudo procesar tu pago. Puedes comunicarte con Yape para saber los motivos o intentar nuevamente con este u otro medio de pago.',
        EN:'Your payment could not be processed. You can contact Yape to find out why or try again with this or another payment method.',
    }
}

export const yapeRejectedInsufficientAmount = {
    ...yapeApproved,
    phone: PHONE_REJECTED_INSUFFICIENT_AMOUNT,
    messageError: {
        PT:'Este pagamento excede seu limite diário para compras online com o Yape. Recomendamos que você pague com outro meio ou tente novamente amanhã.',
        ES:'Este pago excede tu límite diario para compras por internet con Yape. Te recomendamos usar otro medio de pago o volver a intentar mañana.',
        EN:'This payment exceeds your daily limit for online purchases with Yape. We recommend paying with another method or trying again tomorrow.',
    }
}

export const yapeRejectedOtherReason = {
    ...yapeApproved,
    phone: PHONE_REJECTED_OTHER_REASON,
    messageError: {
        PT:'Seu pagamento foi recusado devido a um erro. Recomendamos que você tente novamente ou pague com outro meio.',
        ES:'Tu pago fue rechazado porque hubo un error. Te recomendamos intentar nuevamente o pagar con otro medio de pago.',
        EN:'Your payment was declined because something went wrong. We recommend trying again or paying with another payment method',
    }
}

export const yapeRejectedNotAllowed = {
    ...yapeApproved,
    phone: PHONE_REJECTED_NOT_ALLOWED,
    messageError: {
        PT:'Não foi possível processar seu pagamento. Recomendamos que você pague com outro meio.',
        ES:'No se pudo procesar tu pago. Te recomendamos pagar con otro medio.',
        EN:'Your payment could not be processed. We recommend paying with another method.',
    }
}

export const yapeRejectedMaxAttempts = {
    ...yapeApproved,
    phone: PHONE_REJECTED_MAX_ATTEMPTS,
    messageError: {
        PT:'Por segurança, não é possível pagar com o Yape após inserir os códigos de aprovação incorretamente 3 vezes. Pague com outro meio ou tente novamente em 24 horas.',
        ES:'Luego de 3 códigos de aprobación incorrectos, por tu seguridad, no se puede realizar el pago con Yape. Paga con otro medio o intenta nuevamente en 24 horas.',
        EN:'After three incorrect approval codes, the payment can’t be done with Yape for your safety. Pay with another method or try again in 24 hours.',
    }
}

export const yapeRejectedSecurityCode = {
    ...yapeApproved,
    phone: PHONE_REJECTED_SECURITY_CODE,
    messageError: {
        PT:'Informações inválidas. Revise-as e tente novamente.',
        ES:'Información inválida. Revisa y vuelve a intentarlo.',
        EN:'Invalid information. Please check and try again.',
    }
}

export const yapeRejectedFormError = {
    ...yapeApproved,
    phone: PHONE_REJECTED_FORM_ERROR,
    messageError: {
        PT:'Seu pagamento foi recusado devido a um erro. Recomendamos que você tente novamente ou pague com outro meio.',
        ES:'Tu pago fue rechazado porque hubo un error. Te recomendamos intentar nuevamente o pagar con otro medio de pago.',
        EN:'Your payment was declined because something went wrong. We recommend trying again or paying with another payment method.',
    }
}

export const yapeEmptyFormError = {
    ...yapeApproved,
    code: CODE_EMPTY_FORM_ERROR,
    phone: PHONE_EMPTY_FORM_ERROR,
    messageError: {
        PT:'Preencha este campo.',
        ES:'Completa este campo.',
        EN:'Fill out this field.',
    }
}

export const yapeIncompleteFormError = {
    ...yapeApproved,
    code: CODE_INCOMPLETE_FORM_ERROR,
    phone: PHONE_INCOMPLETE_FORM_ERROR,
    messageError: {
        PT:'Insira o número completo.',
        ES:'Ingresa el número completo.',
        EN:'Enter the entire number.',
    }
}

export const yapeInvalidFormError = {
    ...yapeApproved,
    phone: PHONE_INVALID_FORM_ERROR,
    messageError: {
        PT:'Insira apenas números.',
        ES:'Ingresa solo números.',
        EN:'Enter only numbers.',
    }
}
