(function(definition) {
    if (typeof define === "function" && define.amd) {
        var deps = [];
        if (typeof Q === "function") {
            deps.push(Q);
        } else {
            deps.push('q');
        }
        if (typeof md5 === "function") {
            deps.push(md5);
        } else {
            deps.push('md5');
        }
        define(deps, definition);
    } else {
        throw new Error('request library is AMD packaged and must be use with requirejs');
    }
})(function(Q, md5) {
    /**
     * Read md5 of a blob content
     *
     * @param {Blob} blob
     * @returns {Q.promise}
     */
    return function blobMd5(blob) {
        var deferred = Q.defer();
        var fileReader = new FileReader();
        fileReader.onload = function (e) {
            deferred.resolve(md5.base64(e.target.result, {"b64pad": "="}));
        };
        fileReader.onerror = function(e) {
            deferred.reject(e);
        };
        fileReader.readAsBinaryString(blob);
        return deferred.promise;
    }
});