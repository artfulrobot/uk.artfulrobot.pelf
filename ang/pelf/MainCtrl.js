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

      $routeProvider.when('/pelf/prospect/:id', {
        template: '<pelf-prospect prospect="prospect" ></pelf-prospect>',
        controller: function($scope, $route, prospect) {
          // Pass prospect looked up from id in route to template.
          $scope.prospect = prospect;
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

      $routeProvider.when('/pelf/contract/:id', {
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
  //   myContact -- The current contact, defined above in config().
  angular.module('pelf').controller('PelfMainCtrl', function($scope, crmApi, crmStatus, crmUiHelp, pelf, summary, $location) {
    console.log("PelfMainCtrl");
    console.log('pelf: ', pelf);
    // The ts() and hs() functions help load strings for this module.
    var ts = $scope.ts = CRM.ts('pelf');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/pelf/MainCtrl'}); // See: templates/CRM/pelf/MainCtrl.hlp
    $scope.summary = summary;

    $scope.prospectAdd = function prospectAdd() {
      $location.path("/pelf/prospect/add");
      $location.replace();
    };
    $scope.contractAdd = function contractAdd() {
      $location.path("/pelf/contract/add");
      $location.replace();
    };

    $scope.financialYears = function() {
      return _.keys(summary.prospects_by_fy).sort();
    };
    $scope.friendlyProspectStage = function(machineName) {
      return pelf.prospect.stages[machineName];
    };
    $scope.prospectStages = function() {
      var stages = [];
      _.forEach(summary.prospects_by_fy, function(rows) {
        stages = stages.concat(_.keys(rows));
      });
      stages = _.unique(stages).sort();
      return stages;
    };
    $scope.prospectSubtotal = function(fy) {
      return _.reduce(summary.prospects_by_fy[fy], function(tot, row) {
        return tot + row.scaled;
      }, 0).toLocaleString();
    };

  });

})(angular, CRM.$, CRM._);
