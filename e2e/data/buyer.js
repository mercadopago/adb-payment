export const guestUserMLB = {
    email: process.env.GUEST_EMAIL,
    firstName: "John",
    lastName: "Doe",
    phone: "11997979797",
    address: {
        street: "123 Test St",
        countryId: "BR",
        regionId: "509",
        city: "São Paulo",
        zip: "04533000",
    }
}

export const guestUserMPE = {
    ...guestUserMLB,
    address: {
        street: "123 Test St",
        countryId: "PE",
        regionId: "997",
        city: "Amazonas",
        zip: "04533000",
    },
}
