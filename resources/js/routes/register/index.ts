import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'

export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/register',
} satisfies RouteDefinition<["post"]>

store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

const register = {
    store: Object.assign(store, store),
}

export default register