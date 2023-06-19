
/**
 * Created by maxime on 12/06/14.
 */
/**
 * BatchMailingController
 */
sygeforApp.controller('BatchEMailController', ['$scope', '$http', '$window', '$modalInstance', '$dialogParams', '$dialog', 'config', 'growl', function ($scope, $http, $window, $modalInstance, $dialogParams, $dialog, config, growl) {
    $scope.dialog = $modalInstance;
    $scope.items = $dialogParams.items;
    $scope.targetClass = $dialogParams.targetClass;

    //building templates contents
    $scope.templates = [];
    for (var i in config.templates) {
        $scope.templates[i] = {
            'key': i,
            'label': config.templates[i]['name'],
            'subject': config.templates[i]['subject'],
            'body': config.templates[i]['body'],
            'ical': config.templates[i]['private'],
            'templateAttachments': config.templates[i]['attachmentTemplates'],
            'templateAttachmentChecklist': []
        };
    }

    $scope.templates.unshift({
        'key': -1,
        'label': '',
        'subject': '',
        'body': '',
        'templateAttachments': null,
        'templateAttachmentChecklist': []
    });

    if ($scope.templates.length) {
        $scope.message = {
            template: $scope.templates[0],
            subject: $scope.templates[0]['subject'],
            body: $scope.templates[0]['body'],
            templateAttachments: $scope.templates[0]['templateAttachments'],
            templateAttachmentChecklist: []
        };
    }
    else {
        $scope.message = {
            template: null,
            subject: '',
            body: '',
            templateAttachments: null,
            templateAttachmentChecklist: [],
        };
    }

    $scope.message.attachments = [];
    $scope.formError = '';

    /**
     * ensures the form was correctly filed (sets an error message otherwise), then asks for server-side message sending
     * if mail sending is performed without errors, the file is asked for download
     */
    $scope.ok = function () {
        if (!($scope.message.subject || $scope.message.message)) {
            $scope.formError = 'Pas de corps de message';
            return;
        }

        $scope.formError = '';
        var url = Routing.generate('sygefor_core.batch_operation.execute', {id: 'sygefor_core.batch.email'});
        var data = {
            options: {
                targetClass: $scope.targetClass,
                subject: $scope.message.subject,
                message: $scope.message.body,
                templateAttachments: null,
            },
            attachments: $scope.message.attachments,
            ids: $scope.items.join(",")
        };

        // remove checkbox template values and templateAttachments unchecked
        data.options.templateAttachments = removeUncheckedPublipostTemplate($scope.message.templateAttachments, $scope.message.templateAttachmentChecklist);

        $http({
            method: 'POST',
            url: url,
            transformRequest: function (data) {
                var formData = new FormData();
                //need to convert our json object to a string version of json otherwise
                // the browser will do a 'toString()' on the object which will result
                // in the value '[Object object]' on the server.
                formData.append("options", angular.toJson(data.options));
                //now add all of the assigned files
                formData.append("ids", angular.toJson(data.ids));
                //add each file to the form data and iteratively name them
                for (var key in data.attachments) {
                    formData.append("attachment_" + key, data.attachments[key]);
                }

                return formData;
            },
            headers: {'Content-Type': undefined},
            data: data
        }).success(function (data) {
            growl.addSuccessMessage("Le message a bien été ajouté à la liste d'envoi.");
        });

        $modalInstance.close();
    };

    /**
     * open a new dialog modal corresponding to a batch email operation in preview mode.
     */
    $scope.preview = function () {
        var attachments = angular.copy($scope.message.templateAttachments);
        $dialog.open('batch.emailPreview', {
            ids: $scope.items[0],
            options: {
                targetClass: $scope.targetClass,
                subject: $scope.message.subject,
                message: $scope.message.body,
                templateAttachments: removeUncheckedPublipostTemplate(attachments, $scope.message.templateAttachmentChecklist)
            },
            attachments : $scope.message.attachments
        });
    };

    /**
     * Watches selected template. When changed, current field contents are stored,
     * then replaced byselected template values
     */
    $scope.$watch('message.template', function (newValue, oldValue) {
        if (newValue) {
            //storing changes
            if (oldValue && (oldValue.key != newValue.key)) {
                oldValue.subject = $scope.message.subject;
                oldValue.body = $scope.message.body;
                oldValue.templateAttachments = $scope.message.templateAttachments;
                oldValue.templateAttachmentChecklist = $scope.message.templateAttachmentChecklist;
            }
            //replacing values
            $scope.message.subject = newValue.subject;
            $scope.message.body = newValue.body;
            $scope.message.templateAttachments = newValue.templateAttachments;
            $scope.message.templateAttachmentChecklist = [];
            angular.forEach (newValue.templateAttachments, function(templateAttachment) {
                $scope.message.templateAttachmentChecklist[templateAttachment['id']] = true;
            });
        }
    });

    $scope.fileChanged = function (element, $scope) {
        $scope.$apply(function (scope) {
            for (var key in element.files) {
                if (typeof element.files[key] === "object") {
                    $scope.message.attachments.push(element.files[key]);
                }
            }
        });
        angular.element($('#inputAttachment')).val(null);
    };

    /**
     * Remove file attachment
     * @param key
     */
    $scope.removeAttachment = function(key) {
        $scope.message.attachments.splice(key, 1);
        angular.element($('#inputAttachment')).val(null);
    };

    $scope.isAObject = function(mixed) {
        return typeof mixed === "object";
    };
}]);

/**
 * Remove checkbox template values and templateAttachments unchecked
 * @param templateAttachments
 * @param templateAttachmentChecklist
 * @returns {*}
 */
function removeUncheckedPublipostTemplate(templateAttachments, templateAttachmentChecklist) {
    var attachments = [];
    for (var id in templateAttachmentChecklist) {
        if (templateAttachmentChecklist[id] === true) {
            for (var j in templateAttachments) {
                if ((templateAttachments[j]['id'] + '') === id) {
                    attachments.push(angular.copy(templateAttachments[j]));
                    break;
                }
            }
        }
    }

    return attachments;
}

