import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'

export const csrfCookie = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: csrfCookie.url(options),
    method: 'get',
})

csrfCookie.definition = {
    methods: ["get","head"],
    url: '/sanctum/csrf-cookie',
} satisfies RouteDefinition<["get","head"]>

csrfCookie.url = (options?: RouteQueryOptions) => {
    return csrfCookie.definition.url + queryParams(options)
}

csrfCookie.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: csrfCookie.url(options),
    method: 'get',
})

csrfCookie.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: csrfCookie.url(options),
    method: 'head',
})

const sanctum = {
    csrfCookie: Object.assign(csrfCookie, csrfCookie),
}

export default sanctum