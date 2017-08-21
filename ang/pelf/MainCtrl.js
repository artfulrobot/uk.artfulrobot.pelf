(function(angular, $, _) {
  angular.module('pelf').config(function($routeProvider) {

    $routeProvider.when('/pelf', {
      controller: 'PelfMainCtrl',
      templateUrl: '~/pelf/MainCtrl.html',
      resolve: {
        pelf: 'pelf',
        summary: function(crmApi, $route, $location) {
          // Look up the prospect.
          return crmApi('Activity', 'GetPelfSummary', {})
            .then(function(r) {
              console.log("summary data:" , r);
              return r;
            });
        }
      }
    });

    // We have to resolve the Pelf object and pass it in so it's ready for use.
    // Otherwise, injecting from the factory gives you a promise that you can
    // only interact with via a then().
    $routeProvider.when('/pelf/prospects', {
      controller: ['$scope', 'pelf', function($scope, pelf) {
        $scope.pelf = pelf;
      }],
      resolve: { pelf: 'pelf' },
      template: '<pelf-prospects-list pelf="pelf"></pelf-prospects-list>'
    });

    $routeProvider.when('/pelf/prospects/:id', {
      template: '<pelf-prospect pelf="pelf" prospect="prospect" ></pelf-prospect>',
      controller: function($scope, $route, prospect, pelf) {
        // Pass prospect looked up from id in route to template.
        $scope.prospect = prospect;
        // Ready-resolved pelf object.
        $scope.pelf = pelf;
      },
      resolve: {
        pelf: 'pelf',
        prospect: function(crmApi, $route, $location) {
          // Look up the prospect.
          return crmApi('Activity', 'GetPelfProspect', {
            id: $route.current.params.id,
            with_activities: 1
          })
            .then(function(r) {
              console.log("prospect:" , r);
              return r;
            }, function(e) {
              // @todo issue notice somehow.
              console.warn("error", e);
              $location.path("/pelf/");
              $location.replace();
            });
        }
      }
    });

    $routeProvider.when('/pelf/contracts/:id', {
      template: '<pelf-contract contract="contract" ></pelf-contract>',
      controller: function($scope, $route, contract) {
        // Pass contract looked up from id in route to template.
        $scope.contract = contract;
      },
      resolve: {
        pelf: 'pelf',
        contract: function(crmApi, $route, $location) {
          // Look up the contract.
          return crmApi('Activity', 'GetPelfContract', {
            id: $route.current.params.id,
            with_activities: 1
          })
            .then(function(r) {
              console.log("contract:" , r);
              return r;
            }, function(e) {
              // @todo issue notice somehow.
              console.warn("error", e);
              $location.path("/pelf/");
              $location.replace();
            });
        }
      }
    });
  }
  );

  // The controller uses *injection*. This default injects a few things:
  //   $scope -- This is the set of variables shared between JS and HTML.
  //   crmApi, crmStatus, crmUiHelp -- These are services provided by civicrm-core.
  //   pelf the pelf helper.
  angular.module('pelf').controller('PelfMainCtrl', function($scope, crmApi, crmStatus, crmUiHelp, pelf, summary, $location) {
    console.log("PelfMainCtrl");
    console.log('pelf: ', pelf);
    // The ts() and hs() functions help load strings for this module.
    var ts = $scope.ts = CRM.ts('pelf');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/pelf/MainCtrl'}); // See: templates/CRM/pelf/MainCtrl.hlp
    $scope.summary = summary;

    // These calculations need to be done whenever the summary changes.
    function recalcSummary(summary) {
      // Generate a list of unique financial years.
      summary.financial_years = _.union(
          _.keys(summary.prospects_by_fy),
          _.keys(summary.contracts_by_fy)
        );

      // Generate a list of unique stages.
      summary.prospect_stages = [];
      _.forEach(summary.prospects_by_fy, function(rows) {
        summary.prospect_stages = summary.prospect_stages.concat(_.keys(rows));
      });
      summary.prospect_stages = _.unique(summary.prospect_stages).sort();

      // Generate prospect subtotals, projections etc.
      summary.prospects_total_by_fy = {};
      summary.projection_by_fy = {};
      var max = 0;
      _.forEach(summary.financial_years, function(fy) {

        // Calc total prospects from all stage-subtotals.
        summary.prospects_total_by_fy[fy] = _.reduce(summary.prospects_by_fy[fy],
          function(tot, row) { return tot + row.scaled; }, 0);

        // Initialise to 0 if no contracts in that year.
        if (!summary.contracts_by_fy[fy]) {
          summary.contracts_by_fy[fy] = 0;
        }

        // Calc projection.
        summary.projection_by_fy[fy] = summary.prospects_total_by_fy[fy] + summary.contracts_by_fy[fy];

        max = Math.max(max, summary.projection_by_fy[fy]);
      });
      // multiply value by this scale to get % width.
      summary.bar_scale = max/100;
    }
    recalcSummary(summary);

    $scope.prospectAdd = function prospectAdd() {
      $location.path("/pelf/prospect/add");
      $location.replace();
    };
    $scope.contractAdd = function contractAdd() {
      $location.path("/pelf/contract/add");
      $location.replace();
    };

    $scope.pelf = pelf;
  });

  angular.module('pelf').directive('pelfNav', function() {
    console.log("PelfNav");
    return {
      scope: {
        crumbs: '=',
        actions: '=',
      },
      controller: function() {
      },
      template: '<nav class="pelf-nav">' +
        '<ul class="pelf-nav__crumbs"><li><a href="#/pelf" >Pelf</a></li><li ng-repeat="crumb in crumbs"><a href="{{crumb.path}}">{{crumb.name}}</a></li></ul>' +
        '<ul class="pelf-nav__actions"><li ng-repeat="action in actions"><a href="{{action.path}}" >{{action.name}}</a></li></ul>' +
        '</nav>'
    };
  });

})(angular, CRM.$, CRM._);
