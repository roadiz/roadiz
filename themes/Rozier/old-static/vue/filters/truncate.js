export default function truncate (object, length, offset = 0, ellipsis = "…") {
    if (object && object.length && object.length > length) {
        let str1 = object.substr(0, length + offset);

        return str1 + ellipsis;
    } else {
        return object;
    }
}
