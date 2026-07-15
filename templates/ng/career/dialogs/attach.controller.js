/**
 * Controller for career attachment
 */
sygeforApp.controller('CareerAttachController', ['$scope', '$modalInstance', '$dialogParams', '$dialog', '$state', '$user', '$http', 'form', 'growl', function ($scope, $modalInstance, $dialogParams, $dialog, $state, $user, $http, form, growl) {
    $scope.dialog = $modalInstance;
    $scope.dialog.params = angular.copy($dialogParams);
    $scope.training = $scope.dialog.params.training;
    $scope.form = form;

    /**
     * Sets the trainee in the form object. Used by typeahead.
     * @param item
     */
    $scope.setCareer = function (item) {
        $scope.form.children.career.value = item.value;
    };

    $scope.$watch('selectedCareer', function (value) {
        $scope.form.children.career.value = value ? value.value : '';
    });

    /**
     * gets list of careers that match prefix from server
     * @param pref the prefix that was typed by user
     */
    $scope.getCareerList = function (pref) {
        var url = Routing.generate('career.search');

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
                    value: item.id
                })
            });

            return adresses;
        });
    };

    /**
     * @param data
     */
    $scope.onSuccess = function (data) {
        growl.addSuccessMessage("Le parcours a bien été rattaché.");
        $scope.dialog.close(data);
    };


}]);
