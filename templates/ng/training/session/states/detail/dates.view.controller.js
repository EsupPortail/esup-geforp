/**
 * SessionDetailViewController
 */
sygeforApp.controller('DatesViewController', ['$scope', '$taxonomy', '$dialog', '$trainingBundle', '$user', '$state', '$window','search', 'data', function($scope, $taxonomy, $dialog, $trainingBundle, $user, $state, $window, search, data)
{
    $scope.session = data.session;
    $scope.$trainingBundle = $trainingBundle;
    $scope.form = data.form ? data.form : false;

        /**
     * @param data
     */
    $scope.onSuccess = function(data) {
        $scope.session = data.session;
	    $scope.updateActiveItem($scope.session);
    };

    }]);
