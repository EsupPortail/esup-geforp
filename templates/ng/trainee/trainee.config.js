/**
 * TraineeBundle
 */
sygeforApp.config(["$listStateProvider", "$dialogProvider", "$widgetProvider", function($listStateProvider, $dialogProvider, $widgetProvider) {

    // trainee states
    $listStateProvider.state('trainee', {
        url: "/trainee?q",
        abstract: true,
        templateUrl: "list.html",
        controller:"TraineeListController",
        breadcrumb: [
            { label: "Publics", sref: "trainee.table" }
        ],
        resolve: {
            search: function ($searchFactory, $stateParams, $user) {
                var search = $searchFactory('trainee.search');
                search.query.sorts = {'lastName.source': 'asc'};
//                search.query.filters['institution.name.source'] = $user.organization.institution.name;
                search.extendQueryFromJson($stateParams.q);
                return search.search().then(function() { return search; });
            }
        },
        states: {
            table: {
                url: "",
                icon: "fa-bars",
                label: "Tableau",
                weight: 0,
                controller: 'ListTableController',
                templateUrl: "trainee/states/table/table.html"
            },
            detail: {
                url: "/detail",
                icon: "fa-eye",
                label: "Liste détaillée",
                weight: 1,
                templateUrl: "states/detail/detail.html",
                controller: 'ListDetailController',
                data:{
                    resultTemplateUrl: "trainee/states/detail/result.html"
                },
                states: {
                    view: {
                        url: "/:id",
                        templateUrl: "trainee/states/detail/trainee.html",
                        controller: 'TraineeDetailViewController',
                        resolve: {
                            data: function($http, $stateParams) {
                                var url = Routing.generate('trainee.view', {id: $stateParams.id});
                                return $http({method: 'GET', url: url}).then (function (data) { return data.data; });
                            }
                        },
                        breadcrumb: {
                            label: "{{ data.trainee.fullName }}"
                        }
                    }
                }
            }
        }
    });

    /**
     * DIALOGS
     */
    $dialogProvider.dialog('trainee.create', /* @ngInject */ {
        templateUrl: 'trainee/dialogs/create.html',
        controller: function($scope, $modalInstance, $dialogParams, $state, $http, form, growl) {
            $scope.dialog = $modalInstance;
            $scope.dialog.params = $dialogParams;
            $scope.form = form;
            $scope.onSuccess = function(data) {
                growl.addSuccessMessage("Le stagiaire a bien été créé.");
                $scope.dialog.close(data);
            };
        },
        resolve:{
            form: function ($http){
                return $http.get(Routing.generate('trainee.create')).then(function (response) {
                    return response.data.form;
                });
            }
        }
    });

    /**
     * trainee deletion modal window
     */
    $dialogProvider.dialog('trainee.delete', /* @ngInject */ {
        templateUrl: 'trainee/dialogs/delete.html',
        controller: function($scope, $modalInstance, $dialogParams, $state, $http, growl) {
            $scope.dialog = $modalInstance;
            $scope.dialog.params = $dialogParams;
            $scope.ok = function() {
                var url = Routing.generate('trainee.delete', {id: $dialogParams.trainee.id});
                $http.post(url).then(function (response){
                    growl.addSuccessMessage("Le stagiaire a bien été supprimé.");
                    $scope.dialog.close(response.data);
                });
            };
        }

    });

    /**
     * change trainee activation modal window
     */
    $dialogProvider.dialog('trainee.toggleActivation', /* @ngInject */ {
        templateUrl: 'trainee/dialogs/activation.html',
        controller: function($scope, $modalInstance, $dialogParams, $state, $http, growl) {
            $scope.dialog = $modalInstance;
            $scope.dialog.params = $dialogParams;
            $scope.ok = function() {
                var url = Routing.generate('trainee.toggleActivation', {id: $dialogParams.trainee.id});
                $http.post(url).then(function (response){
                    growl.addSuccessMessage("Le stagiaire a bien été mis à jour.");
                    $scope.dialog.close(response.data);
                });
            };
        }
    });

    /**
     * WIDGETS
     */
    var date = new Date();
    date.setMonth(date.getMonth() - 2);
    $widgetProvider.widget("trainee", /* @ngInject */ {
        controller: 'WidgetListController',
        templateUrl: 'trainee/widget/trainee.html',
        options: function($user, $filter) {
            return {
                route: 'trainee.search',
                rights: ['sygefor_trainee.rights.trainee.own.view', 'sygefor_trainee.rights.trainee.all.view'],
                state: 'trainee.table',
                title: 'Derniers stagiaires inscrits',
                size: 10,
                filters:{
//                    'institution.name.source': $user.organization.institution.name,
                    "createdat": {
                        "type": "range",
                        "gte": $filter('date')(date, 'yyyy-MM-dd', 'Europe/Paris')
                    }
                },
                sorts: {'createdat': 'desc'}
            }
        }
    });
}]);
