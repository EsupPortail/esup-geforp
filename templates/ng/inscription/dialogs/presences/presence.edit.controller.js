/**
 * Core List Controller
 */
sygeforApp.controller('PresenceEditController', ['$scope', '$modalInstance', '$dialog', '$dialogParams', '$state', '$user', '$http', '$window', 'growl', 'data', function ($scope, $modalInstance, $dialog, $dialogParams, $state, $user, $http, $window, growl, data, $moment) {

    $scope.dialog = $modalInstance;
    $scope.dialog.params = $dialogParams;
    $scope.form = data.form;
    $scope.presence = data.presence;
    //$scope.$apply();

    /**
     * open an inscription creation window, then process the return by adding inscription
     * @param presence
     */
    $scope.editPresence = function (presence) {
        $dialog.open('presence.edit', {presence: presence}).then(function (data){
            presence = data.presence;
        });
    };

    /**
     *
     * @param data
     */
    $scope.onSuccess = function (data) {
        growl.addSuccessMessage("Les présences sur la journée ont bien été mises à jour");
        $scope.dialog.close(data);
        location.reload();
    };

}]);

