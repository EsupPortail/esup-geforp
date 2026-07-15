/**
 * Trainer Add Controller
 */
sygeforApp.controller('TrainingAddController', ['$scope', '$modalInstance', '$dialog', '$dialogParams', '$state', '$http', 'form', 'growl', function ($scope, $modalInstance, $dialog, $dialogParams, $state, $http, form, growl) {
    $scope.dialog = $modalInstance;
    $scope.dialog.params = $dialogParams;
    $scope.form = form;

    /**
     * Sets the training in the form object. Used by typeahead.
     * @param item
     */
    $scope.setTraining = function (item) {
        $scope.form.children.training.value = item.value;
    };

    $scope.$watch('selectedTraining', function (value) {
        $scope.form.children.training.value = value ? value.value : '';
    });

    /**
     * gets list of training that match prefix from server
     * @param pref the prefix that was typed by user
     */
    $scope.getTrainingList = function (pref) {
        var url = Routing.generate('training.search');

        return $http.post(url, {
                "query": {
                    "match": {
                        "name.autocomplete": {
                            "query": angular.lowercase(pref)
                        }
                    }
                }
            }
        ).then(function (res) {
            var adresses = [];
            angular.forEach(res.data.items, function (item) {
                adresses.push({
                    label: item.name,
                    value: item.id,
                    organization: (item.organization.name) ? item.organization.name : ''
                })
            });

            return adresses;
        });
    };

    /**
     * @param data
     */
    $scope.onSuccess = function (data) {
        growl.addSuccessMessage("Le stage a bien été ajouté au parcours.");
        $scope.dialog.close(data);
    };

    /**
     * manages training creation dialog and its return.
     **/
    $scope.createTraining = function () {
        $dialog.open('training.create').then(function (result) {
            var training = {
                label: result.training.name,
                value: result.training.id
            };
            $scope.selectedTraining = training;
            $scope.setTraining(training);
        });
    }

}]);

