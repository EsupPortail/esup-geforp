/**
 * BatchMailingController
 */
sygeforApp.controller('InscriptionStatusChange', ['$scope', '$http', '$window', '$modalInstance', '$dialogParams', '$dialog', 'config', function ($scope, $http, $window, $modalInstance, $dialogParams, $dialog, config)
{
    $scope.service = 'sygefor_inscription.batch.inscription_status_change';
    $scope.dialog = $modalInstance;
    $scope.items = $dialogParams.items;
    $scope.inscriptionStatus = $dialogParams.inscriptionStatus;
    $scope.presenceStatus = $dialogParams.presenceStatus;
    $scope.send = {Mail: !!config.templates.length};
    $scope.attachmentTemplates = config.attachmentTemplates;
    $scope.attachments = [];
    $scope.attCheckList = $scope.attachmentTemplates;
    $scope.formError = '';

    //building templates contents
    $scope.templates = [];
    for (var i in config.templates) {
        $scope.templates[i] = {
            'key': i,
            'label': config.templates[i]['name'],
            'subject': config.templates[i]['subject'],
            'body': config.templates[i]['body'],
            'attachmentTemplates': config.templates[i]['attachmentTemplates']
        };
    }

    $scope.message = {};

    if ($scope.templates.length) {
        $scope.message.template = $scope.templates[0];
        $scope.message.subject = $scope.templates[0].subject;
        $scope.message.body = $scope.templates[0].body;
    }

    /**
     * ensures the form was correctly filed (sets an error message otherwise), then asks for server-side message sending
     * if mail sending is performed without errors, the file is asked for download
     */
    $scope.ok = function () {

        if ($scope.send.Mail && !($scope.message.subject || $scope.message.message)) {
            $scope.formError = 'Pas de corps de message';
            return;
        }

        $scope.formError = '';
        var url = Routing.generate('sygefor_core.batch_operation.execute', {id: $scope.service});

        var attTemplates = [];
        if (typeof $scope.attCheckList != 'undefined') {
            angular.forEach($scope.attCheckList, function (tpl) {
                if (( typeof tpl.selected != 'undefined' ) && tpl.selected) {
                    attTemplates.push(tpl.id);
                }
            });
        }

        var data = {
            options: {
                //inscriptionStatus: $scope.inscriptionStatus.id,
                targetClass: 'SygeforInscriptionBundle:AbstractInscription',
                sendMail: $scope.send.Mail,
                subject: $scope.message.subject,
                message: $scope.message.body,
                attachmentTemplates: attTemplates,
                objects: {'SygeforTrainingBundle:Session': ($dialogParams.session) ? $dialogParams.session.id : 0}
            },
            ids: $scope.items.join(",")
        };

        if (typeof $scope.inscriptionStatus != 'undefined') {
            data['options']['inscriptionStatus'] = $scope.inscriptionStatus.id
        }

        if (typeof $scope.presenceStatus != 'undefined') {
            data['options']['presenceStatus'] = $scope.presenceStatus.id
        }

        $http.post(url, data).success(function() {
            $scope.dialog.close();
        });
    };

    $scope.preview = function () {
        $dialog.open('batch.emailPreview', {
            ids: $scope.items[0],
            options: {
                targetClass: 'SygeforInscriptionBundle:AbstractInscription',
                subject: $scope.message.subject,
                message: $scope.message.body
            }
        });
    };

    $scope.previewAttachment = function (attachmentTemplate) {

        var url = Routing.generate('sygefor_core.batch_operation.execute', {id: 'sygefor_core.batch.publipost.inscription'});

        var data = {
            options: {
                template: attachmentTemplate.id
            },
            ids: $scope.items[0]
        };

        $http(
            {
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

                    return formData;
                },
                headers: {'Content-Type': undefined},
                data: data
            }).success(
            function (data) { //response should contain the file url
                if (data.fileUrl) {
                    var url = Routing.generate('sygefor_core.batch_operation.get_file', {
                        service: 'sygefor_core.batch.publipost.inscription',
                        file: data.fileUrl,
                        filename: attachmentTemplate.fileName,
                        pdf: true
                    });
                    // changin location :
                    $window.location = url;
                }
            });

    };

    $scope.cancel = function () {
        $modalInstance.dismiss();
    };

    /**
     * watches file upload model, and updates the form accordingly
     */
    $scope.fileChanged = function (element, $scope) {
        $scope.$apply(function (scope) {
            $scope.options.templateFile = element.files[0];
        });
    };

    /**
     * Watches selected template. When changed, current field contents are stored,
     * then replaced byselected template values
     */
    $scope.$watch('message.template', function (newValue, oldValue) {
        if (newValue) {
            //storing changes
            if (typeof oldValue != 'undefined') {
                oldValue.subject = $scope.message.subject;
                oldValue.body = $scope.message.body;
            }
            //replacing values
            $scope.message.subject = newValue.subject;
            $scope.message.body = newValue.body;
            $scope.attCheckList = newValue.attachmentTemplates;
        }
    });
}]);
