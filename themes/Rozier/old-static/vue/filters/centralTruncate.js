export default function centralTruncate (object, length, offset = 0, ellipsis = "[…]") {
    if (object && object.length && object.length > length + ellipsis.length) {
        let str1 = object.substr(0, Math.floor(length / 2) + Math.floor(offset / 2));
        let str2 = object.substr((Math.floor(length / 2) * -1) + Math.floor(offset / 2));

        return str1 + ellipsis + str2;
    } else {
        return object;
    }
}
