/**
 * Core List Controller
 */
sygeforApp.controller('PresencesViewController', ['$scope', '$dialog', '$filter', '$taxonomy', '$timeout', '$q', function($scope, $dialog, $filter, $form, $taxonomy, $timeout) {

    /**
     * Edit presences
     */
    $scope.editPresence = function () {
        $dialog.open('presence.edit', {presence: this.presence}).then(function (data){
            presence = data.presence;
        });
    };


}]);

