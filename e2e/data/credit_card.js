const amexApro = {
    name: "APRO",
    number: process.env.CC_AMEX,
    month: "11",
    year: "25",
    code: "1234",
    document: process.env.CPF
};

const masterApro = {
    ...amexApro,
    number: process.env.CC_MASTER,
    code: "123"
};

export { amexApro, masterApro };
