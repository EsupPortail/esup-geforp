sygeforApp.controller('InstitutionDetailViewController', ['$scope', '$taxonomy', '$dialog', '$http', '$window', '$user', '$state', 'search', 'data', function($scope, $taxonomy, $dialog, $http, $window, $user, $state, search, data) {

    $scope.institution = data.institution;
    $scope.form = data.form ? data.form : false;
    $scope.$moment = moment;
    $scope.$user = $user;

    $scope.onSuccess = function(data) {
        $scope.displayNewTrainingCorrespondentForm = false;
	    $scope.institution = data.institution;
	    $scope.updateActiveItem($scope.institution);
    };

    /**
     * Change Organization
     */
    $scope.changeOrganization = function () {
        $dialog.open('institution.changeOrg', {institution: $scope.institution}).then(function(data) {
            $scope.institution = data.institution;
        });
    };

    /**
     * Delete the institution
     */
    $scope.delete = function () {
        $dialog.open('institution.delete', {institution: $scope.institution}).then(function (){
            $state.go('institution.table', null, { reload:true });
        });
    };
}]);
