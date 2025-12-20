const slugify = (text, separator = "-") => {
    return text
        .toLowerCase()
        .replace(/[^\w\s-]/g, "") // Remove special characters
        .replace(/\s+/g, separator) // Replace spaces with hyphens
        .replace(/--+/g, "-") // Replace multiple hyphens with single hyphen
        .replace(/(^-+)|(-+$)/g, ""); // Remove leading and trailing hyphens
};

const unslugify = (text, separator = "-") => {
    return text.replace(new RegExp(separator, "g"), " ");
};

const capitalize = (text) => {
    return text.replace(/\b\w/g, (char) => char.toUpperCase());
};

export { slugify, unslugify, capitalize };
