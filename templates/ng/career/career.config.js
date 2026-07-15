/**
 * careerBundle
 */
sygeforApp.config(["$listStateProvider", "$dialogProvider", function ($listStateProvider, $dialogProvider) {

    // career states
    $listStateProvider.state('career', {
        url: "/career?q",
        abstract: true,
        templateUrl: "list.html",
        controller: "CareerListController",
        breadcrumb: [
            {label: "Parcours", sref: "career.table"}
        ],
        resolve: {
            search: function ($searchFactory, $stateParams, $user) {
                var search = $searchFactory('career.search');
                search.query.sorts = {'name.source': 'asc'};
                search.extendQueryFromJson($stateParams.q);
                return search.search().then(function () {
                    return search;
                });
            }
        },
        states: {
            table: {
                url: "",
                icon: "fa-bars",
                label: "Tableau",
                weight: 0,
                controller: 'ListTableController',
                templateUrl: "career/states/table/table.html"
            },
            detail: {
                url: "/detail",
                icon: "fa-eye",
                label: "Liste détaillée",
                weight: 1,
                templateUrl: "states/detail/detail.html",
                controller: 'ListDetailController',
                data: {
                    resultTemplateUrl: "career/states/detail/result.html"
                },
                states: {
                    view: {
                        url: "/:id",
                        templateUrl: "career/states/detail/career.html",
                        controller: 'CareerDetailViewController',
                        resolve: {
                            data: function ($http, $stateParams) {
                                var url = Routing.generate('career.view', {id: $stateParams.id});
                                return $http({method: 'GET', url: url}).then(function (data) {
                                    return data.data;
                                });
                            }
                        },
                        breadcrumb: {
                            label: "{{ data.career.name }}"
                        }
                    }
                }
            }
        }
    });

    /**
     * DIALOGS
     */
    $dialogProvider.dialog('career.create', /* @ngInject */ {
        templateUrl: 'career/dialogs/create.html',
        controller: function ($scope, $modalInstance, $dialogParams, $state, $http, form, growl) {
            $scope.dialog = $modalInstance;
            $scope.dialog.params = $dialogParams;
            $scope.form = form;
            $scope.onSuccess = function (data) {
                growl.addSuccessMessage("Le parcours a bien été créé.");
                $scope.dialog.close(data);
            };
        },
        resolve: {
            form: function ($http) {
                return $http.get(Routing.generate('career.create')).then(function (response) {
                    return response.data.form;
                });
            }
        }
    });

    $dialogProvider.dialog('career.attach', /* @ngInject */ {
        templateUrl: 'career/dialogs/attach.html',
        controller: function ($scope, $modalInstance, $dialogParams, $state, $http, form, growl) {
            $scope.dialog = $modalInstance;
            $scope.dialog.params = $dialogParams;
            $scope.form = form;
            $scope.onSuccess = function (data) {
                growl.addSuccessMessage("Le parcours a bien été rattaché.");
                $scope.dialog.close(data);
            };
        },
        resolve: {
            form: function ($http) {
                return $http.get(Routing.generate('career.attach')).then(function (response) {
                    return response.data.form;
                });
            }
        }
    });

    /**
     * career deletion modal window
     */
    $dialogProvider.dialog('career.delete', /* @ngInject */ {
        templateUrl: 'career/dialogs/delete.html',
        controller: function ($scope, $modalInstance, $dialogParams, $state, $http, growl) {
            $scope.dialog = $modalInstance;
            $scope.dialog.params = $dialogParams;
            $scope.ok = function () {
                var url = Routing.generate('career.delete', {career: $dialogParams.career.id});
                $http.post(url).then(function (response) {
                    growl.addSuccessMessage("Le parcours a bien été supprimé.");
                    $scope.dialog.close(response.data);
                });
            };
        }
    });

    /**
     * training.remove : simple confirmation modal for training remove
     */
    $dialogProvider.dialog('training.remove', /* @ngInject */ {
        templateUrl: 'career/dialogs/training-remove.html',
        controller: 'TrainingRemoveController'
    });

    /**
     * training.add : associate a new training to a career
     */
    $dialogProvider.dialog('training.add', /* @ngInject */ {
        controller: 'TrainingAddController',
        templateUrl: "career/dialogs/training-add.html",
        resolve: {
            form: function ($http, $dialogParams) {
                return $http.get(Routing.generate('training.attach', {'career': $dialogParams.career})).then(function (response) {
                    return response.data.form;
                });
            }
        }
    });
}]);
