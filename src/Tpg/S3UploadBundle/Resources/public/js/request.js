(function(definition) {
    if (typeof define === "function" && define.amd) {
        var deps = [];
        if (typeof Q === "function") {
            deps.push(Q);
        } else {
            deps.push('q');
        }
        define(deps, definition);
    } else {
        throw new Error('request library is AMD packaged and must be use with requirejs');
    }
})(function(Q) {
    /**
     * Request to Server
     *
     * @param {String} url
     * @param {String} method
     * @param {String} params
     * @param {String[]} headers
     *
     * @returns {Q.promise}
     */
    return function request(url, method, params, headers, monitorUpload) {
        var request = new XMLHttpRequest();
        var deferred = Q.defer();
        var promise = deferred.promise;
        var size = params.size;
        if (monitorUpload === true) {
            request.upload.addEventListener("progress", function(e) {
                if (e.lengthComputable) {
                    deferred.notify((e.loaded / size) * 100);
                }
            }, false);
        }
        request.open(method, url, true);
        if (typeof headers == "object") {
            for (var field in headers) {
                if (headers.hasOwnProperty(field)) {
                    request.setRequestHeader(field, headers[field]);
                }
            }
        }
        request.onload = function() {
            if (request.status === 200) {
                var responseHeaders = request.getAllResponseHeaders().split("\r\n");
                var headers = {};
                for (var i in responseHeaders) {
                    if (responseHeaders.hasOwnProperty(i)) {
                        var item = responseHeaders[i].split(':');
                        if (item.length == 2) {
                            headers[item[0]] = item[1].replace(/^\s/, '').replace(/\s$/, '').replace(/"/g, '');
                        }
                    }
                }
                deferred.resolve({
                    status: request.status,
                    statusText: request.statusText,
                    headers: headers,
                    responseText: request.responseText
                });
            } else {
                deferred.reject(new Error("Status code was " + request.status));
            }
        };
        request.onerror = function() {
            deferred.reject(new Error("Can't XHR"));
        };
        request.onprogress = function(event) {
            deferred.notify(event.loaded / event.total);
        };
        if (params) {
            request.send(params);
        } else {
            request.send();
        }
        return promise;
    };
});