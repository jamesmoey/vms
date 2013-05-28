(function(definition) {
    if (typeof define === "function" && define.amd) {
        var deps = ['lib/request', 'lib/blobmd5'];
        if (typeof Q === "function") {
            deps.push(Q);
        } else {
            deps.push('q');
        }
        if (typeof _ === "function") {
            deps.push(_);
        } else {
            deps.push('underscore');
        }
        define(deps, definition);
    } else {
        throw new Error('upload library is AMD packaged and must be use with requirejs');
    }
})(function(request, blobMD5, Q, _) {

    /**
     * S3 Upload internal implementation
     *
     * @param {File} file
     * @constructor
     */
    function S3Upload(file, multipart) {
        var fileMd5 = false;
        _.extend(this, S3UploadBase);
        if (multipart) {
            this.multipart = multipart;
        }
        if (file.size > 1024*1024*15 || multipart) {
            _.extend(this, S3UploadMulti);
        } else {
            _.extend(this, S3UploadSingle);
        }
        /**
         * Get File
         *
         * @returns {File}
         */
        this.getFile = function() {
            return file;
        };
        this.getFileMD5 = function() {
            return fileMd5;
        };
        this.setFileMd5 = function(md5) {
            fileMd5 = md5;
            return this;
        };
    }

    var S3UploadBase = {
        /**
         * Kick off the process.
         *
         * @param {Q.defer} deferred
         */
        start: function(deferred) {
            var me = this;
            var request;
            this.deferred = deferred;
            if (this.multipart) {
                request = this.upload(this.multipart);
            } else {
                request = this.startRequest()
                    /** Start the upload now */
                    .then(function(response) {
                        return me.upload(JSON.parse(response.responseText));
                    }, function(error) {
                        deferred.reject(error);
                    });
            }
            request.done(function(response) {
                deferred.resolve(JSON.parse(response.responseText));
            }, function(error) {
                me.abort();
                deferred.reject(error);
            }, function(progress) {
                if (progress >= 1 && progress <= 100) {
                    deferred.notify(progress);
                }
            });
        }
    };

    var S3UploadMulti = {
        /**
         * Start the request
         *
         * @returns {Q.promise}
         */
        startRequest: function() {
            return request(
                '/api/s3/multiparts.json',
                'POST',
                JSON.stringify({
                    size: this.getFile().size,
                    key: this.getFile().name,
                    mime_type: this.getFile().type
                }),
                { "Content-Type": "application/json" }
            );
        },
        /**
         * Upload the resource.
         *
         * @param multipart
         *
         * @returns {Q.promise}
         */
        upload: function(multipart) {
            var deferred = Q.defer();
            var me = this;
            var promise = deferred.promise;
            for(var c = 1; c <= multipart.number_of_part; c++) {
                if (!multipart.completed_part[c]) {
                    promise = promise.then(uploadPart(c));
                }
            }
            deferred.resolve();
            return promise;

            function uploadPart(partNumber) {
                return function() {
                    var resourceContent = me.getFile().slice(
                        (partNumber-1)*multipart.size_per_part,
                        (partNumber*multipart.size_per_part>multipart.size)?multipart.size:partNumber*multipart.size_per_part,
                        multipart.mime_type
                    );
                    /** Get Upload Signature */
                    return blobMD5(resourceContent).then(function(hash) {
                        return request(
                            '/api/s3/multiparts/'+multipart.id+'/nexts/1.json',
                            'PUT',
                            JSON.stringify({md5: hash}),
                            { "Content-Type": "application/json" }
                        ).then(
                            function(response) {
                                /**
                                 * Upload content to S3
                                 */
                                response = JSON.parse(response.responseText);
                                return request(
                                    '//'+multipart.bucket+".s3.amazonaws.com"+'/'+multipart.key+'?partNumber='+partNumber+'&uploadId='+multipart.upload_id,
                                    'PUT',
                                    resourceContent,
                                    {
                                        "Content-Type": multipart.mime_type,
                                        "x-amz-date": response['x-amz-date'],
                                        "Authorization": response['authorisations'][partNumber],
                                        "Content-MD5": hash
                                    },
                                    true
                                ).progress(function(progress) {
                                    if (progress >= 1 && progress <= 100) {
                                        return ( ((partNumber-1)*multipart.size_per_part + (progress/100*multipart.size_per_part)) / multipart.size ) * 100;
                                    }
                                });
                            },
                            null
                        ).then(
                            function(response) {
                                /**
                                 * Done.
                                 */
                                var etag = response.headers["ETag"];
                                return request(
                                    '/api/s3/multiparts/'+multipart.id+'/completes/'+partNumber+'.json',
                                    'PUT',
                                    JSON.stringify({
                                        'etag': etag
                                    }),
                                    { "Content-Type": "application/json" }
                                ).then(function(response) {
                                    /** Send notification to the top level */
                                    me.deferred.notify(JSON.parse(response.responseText));
                                    return response;
                                });
                            }
                        );
                    });
                }
            }
        },
        abort: function() {

        }
    };

    var S3UploadSingle = {
        startRequest: function() {
            var me = this;
            return blobMD5(this.getFile()).then(function(hash) {
                me.setFileMd5(hash);
                return request(
                    '/api/s3/upload/signature',
                    'POST',
                    JSON.stringify({
                        md5: hash,
                        mime_type: me.getFile().type,
                        key: me.getFile().name
                    }),
                    { "Content-Type": "application/json" }
                );
            });
        },
        upload: function(result) {
            var me = this;
            return request(
                '//'+result.bucket+".s3.amazonaws.com"+'/'+result.key,
                'PUT',
                this.getFile(),
                {
                    "Content-Type": this.getFile().type,
                    "x-amz-date": result.date,
                    "Authorization": result.authorisation,
                    "Content-MD5": this.getFileMD5()
                },
                true
            ).then(function(response) {
                var url = '/api/s3/upload/complete/'+me.getFile().name;
                if ('x-amz-version-id' in response.headers) {
                    url += '/' + response.headers['x-amz-version-id'];
                }
                return request(
                    url,
                    'POST'
                );
            });
        },
        abort: function() {

        }
    };

    /**
     * Upload a file object.
     * @param {File} file
     * @return {Q.promise}
     */
    return function upload (file, multipart) {
        var deferred = Q.defer();
        var upload = new S3Upload(file, multipart);
        upload.start(deferred);
        return deferred.promise;
    };
});