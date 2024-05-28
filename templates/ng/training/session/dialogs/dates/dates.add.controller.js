/**
 * Trainer Add Controller
 */
sygeforApp.controller('DatesAddController', ['$scope', '$modalInstance', '$dialog', '$dialogParams', '$state', '$user', '$http', '$window', 'form', 'growl', function ($scope, $modalInstance, $dialog, $dialogParams, $state, $user, $http, $window, form, growl) {

    $scope.dialog = $modalInstance;
    $scope.dialog.params = angular.copy($dialogParams);
    $scope.session = $dialogParams.session;
    $scope.form = form;

    /**
     * open an inscription creation window, then process the return by adding inscription
     * @param session
     */
    $scope.addDates = function () {
        $dialog.open('add.dates', {session: $scope.session}).then(function (data){
            $scope.session.dates.push(data.date);
        });
    }

    /**
     *
     * @param data
     */
    $scope.onSuccess = function (data) {
        growl.addSuccessMessage("La date a bien été ajoutée à la session.");
        $scope.dialog.close(data);
        location.reload();
    };
}]);


