import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'

export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/settings/two-factor',
} satisfies RouteDefinition<["get","head"]>

show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

const TwoFactorAuthenticationController = { show }

export default TwoFactorAuthenticationController